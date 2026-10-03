<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\FileUploader;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\RatingRepository;
use App\Repositories\StatusLogRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\TicketService;
use App\Services\TicketStatusService;

final class TicketController
{
    public function __construct(
        private TicketRepository $tickets,
        private CategoryRepository $categories,
        private UserRepository $users,
        private CommentRepository $comments,
        private StatusLogRepository $logs,
        private RatingRepository $ratings,
        private TicketService $ticketService,
        private TicketStatusService $statusService,
        private FileUploader $uploader,
        private Auth $auth,
        private View $view
    ) {}

    /** GET /tickets — User เห็นเฉพาะของตัวเอง, Technician/Admin เห็นทั้งหมด (กรองได้) */
    public function index(Request $r, array $p = []): Response
    {
        $filters = [
            'status'   => $r->query['status'] ?? '',
            'priority' => $r->query['priority'] ?? '',
        ];
        if ($this->auth->role() === 'user') {
            $filters['user_id'] = $this->auth->id();
        } elseif ($this->auth->role() === 'technician' && !empty($r->query['mine'])) {
            $filters['technician_id'] = $this->auth->id();
        }
        $keyword = trim((string) ($r->query['q'] ?? ''));

        return $this->view->render('tickets/index', [
            'tickets' => $this->tickets->search($keyword, $filters),
            'keyword' => $keyword,
            'filters' => $filters,
            'mine'    => !empty($r->query['mine']),
        ]);
    }

    public function create(Request $r, array $p = []): Response
    {
        return $this->view->render('tickets/create', ['categories' => $this->categories->all()]);
    }

    /** POST /tickets — ตอบ 201 JSON เมื่อเรียกด้วย AJAX (ตาม Sequence 6.1) */
    public function store(Request $r, array $p = []): Response
    {
        $v = Validator::make($r->input, [
            'title'       => 'required|min:5|max:150',
            'description' => 'required|min:10|max:5000',
            'category_id' => 'required|int',
            'priority'    => 'required|in:' . implode(',', array_column(TicketPriority::cases(), 'value')),
        ]);
        if (!$v->fails() && !$this->categories->find((int) $r->input('category_id'))) {
            throw new HttpException(422, 'หมวดหมู่ไม่ถูกต้อง', ['category_id' => ['หมวดหมู่ไม่ถูกต้อง']]);
        }
        if ($v->fails()) {
            if ($r->wantsJson()) {
                return Response::json(['ok' => false, 'message' => $v->firstError(), 'errors' => $v->errors()], 422);
            }
            with_old($r->input);
            flash('error', $v->firstError());
            return Response::redirect(url('/tickets/create'));
        }

        $ticket = $this->ticketService->createTicket($r->input, $r->file('image'), (int) $this->auth->id());
        $redirect = url('/tickets/' . $ticket['id']);

        if ($r->wantsJson()) {
            return Response::json(['ok' => true, 'id' => (int) $ticket['id'], 'redirect' => $redirect], 201);
        }
        flash('success', 'แจ้งซ่อมสำเร็จ หมายเลข #' . $ticket['id']);
        return Response::redirect($redirect);
    }

    public function show(Request $r, array $p): Response
    {
        $ticket = $this->findAuthorized((int) $p['id']);
        $actor  = $this->auth->user();
        $status = TicketStatus::from($ticket['status']);

        $isAssignedTech = $actor['role'] === 'technician' && (int) $ticket['technician_id'] === (int) $actor['id'];
        $isOwner        = (int) $ticket['user_id'] === (int) $actor['id'];

        // ปุ่ม/ฟอร์มที่แต่ละบทบาทเห็น (ตรงกับ Transition Rules Matrix)
        $actions = [
            'assign'  => $actor['role'] === 'admin' && $status === TicketStatus::Open,
            'accept'  => $isAssignedTech && $status === TicketStatus::Assigned,
            'resolve' => $isAssignedTech && $status === TicketStatus::InProgress,
            'close'   => $isOwner && $status === TicketStatus::Resolved,
            'reject'  => $isOwner && $status === TicketStatus::Resolved,
            'comment' => $this->ticketService->canComment($ticket, $actor),
        ];

        return $this->view->render('tickets/show', [
            'ticket'      => $ticket,
            'status'      => $status,
            'comments'    => $this->comments->findByTicket((int) $ticket['id']),
            'logs'        => $this->logs->findByTicket((int) $ticket['id']),
            'rating'      => $this->ratings->findByTicket((int) $ticket['id']),
            'technicians' => $actions['assign'] ? $this->users->byRole('technician') : [],
            'actions'     => $actions,
        ]);
    }

    /** PATCH|POST /api/tickets/{id}/status (Sequence 6.2) */
    public function updateStatus(Request $r, array $p): Response
    {
        $ticket = $this->findAuthorized((int) $p['id']);
        $to = TicketStatus::tryFrom((string) $r->input('status'));
        if (!$to) {
            throw new HttpException(422, 'สถานะไม่ถูกต้อง', ['status' => ['สถานะไม่ถูกต้อง']]);
        }

        $updated = $this->statusService->transition($ticket, $to, $this->auth->user(), $r->input, $r->file('image'));

        return Response::json([
            'ok'      => true,
            'message' => 'อัปเดตสถานะเป็น "' . $to->label() . '" แล้ว',
            'ticket'  => ['id' => (int) $updated['id'], 'status' => $to->value, 'label' => $to->label(), 'badge' => $to->badgeClass()],
        ]);
    }

    /** POST /api/tickets/{id}/comments */
    public function addComment(Request $r, array $p): Response
    {
        $ticket = $this->findAuthorized((int) $p['id']);
        $v = Validator::make($r->input, ['body' => 'required|max:2000']);
        if ($v->fails()) {
            return Response::json(['ok' => false, 'message' => $v->firstError(), 'errors' => $v->errors()], 422);
        }
        $c = $this->ticketService->addComment($ticket, $this->auth->user(), (string) $r->input('body'), $r->file('image'));

        return Response::json(['ok' => true, 'comment' => [
            'id'     => (int) $c['id'],
            'author' => $c['author_name'],
            'role'   => $c['author_role'],
            'body'   => $c['body'],
            'image'  => $c['image_path'] ? url('/uploads/' . $c['image_path']) : null,
            'time'   => format_dt($c['created_at']),
        ]], 201);
    }

    /** GET /uploads/{file} — รูปอยู่นอก DocumentRoot จึงต้องเสิร์ฟผ่าน PHP พร้อมตรวจสิทธิ์ */
    public function image(Request $r, array $p): Response
    {
        $file = basename($p['file']);
        $ticketId = $this->comments->ticketIdByImage($file);
        if ($ticketId === null) {
            throw new HttpException(404, 'ไม่พบไฟล์');
        }
        $this->findAuthorized($ticketId);
        $path = $this->uploader->path($file);
        if (!is_file($path)) {
            throw new HttpException(404, 'ไม่พบไฟล์');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        return Response::raw((string) file_get_contents($path), $mime);
    }

    private function findAuthorized(int $id): array
    {
        $ticket = $this->tickets->findDetailed($id);
        if (!$ticket) {
            throw new HttpException(404, 'ไม่พบ Ticket');
        }
        if (!$this->ticketService->canView($ticket, $this->auth->user())) {
            throw new HttpException(403, 'คุณไม่มีสิทธิ์เข้าถึง Ticket นี้');
        }
        return $ticket;
    }
}

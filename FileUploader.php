<?php
declare(strict_types=1);

namespace App\Core;

/** อัปโหลดรูปอย่างปลอดภัย: ตรวจ error, ขนาด, MIME จริง (finfo), เป็นรูปจริง และสุ่มชื่อไฟล์ใหม่ */
final class FileUploader
{
    public const MAX_SIZE = 2 * 1024 * 1024; // 2MB
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(private string $dir) {}

    public static function hasFile(?array $file): bool
    {
        return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return string ชื่อไฟล์ที่เก็บ (ไม่รวม path) */
    public function upload(array $file): string
    {
        $fail = fn(string $m) => new HttpException(422, $m, ['image' => [$m]]);

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw $fail('อัปโหลดไฟล์ไม่สำเร็จ (code ' . ($file['error'] ?? '?') . ')');
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw $fail('ไฟล์ใหญ่เกิน 2MB');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw $fail('ไฟล์ไม่ถูกต้อง');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw $fail('อนุญาตเฉพาะไฟล์ JPG, PNG, WEBP');
        }
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0750, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        if (!move_uploaded_file($file['tmp_name'], $this->dir . '/' . $name)) {
            throw new HttpException(500, 'บันทึกไฟล์ไม่สำเร็จ');
        }
        return $name;
    }

    public function delete(?string $name): void
    {
        if ($name) {
            @unlink($this->path($name));
        }
    }

    public function path(string $name): string
    {
        return $this->dir . '/' . basename($name);
    }
}

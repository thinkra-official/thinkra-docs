# Thinkra Docs

نظام إدارة محتوى الدورات التعليمية لمنصة Thinkra. كل أستاذ يدخل برقم الهاتف ويرى كورساته فقط، ويكتب وينظم محتوى الدروس.

## المتطلبات

- PHP 8.3+
- MySQL 8+
- Composer 2.x
- Laragon أو XAMPP (Windows)
- امتدادات PHP: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`

## التشغيل المحلي (Laragon / XAMPP)

### 1. استنساخ المشروع

```bash
cd E:\!!WEBSITES!!\thinkra-docs
composer install
```

### 2. إنشاء قاعدة البيانات

من phpMyAdmin أو MySQL CLI:

```sql
CREATE DATABASE thinkra_docs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. إعداد `.env`

```bash
copy .env.example .env
```

عدّل القيم:

```env
APP_URL=http://thinkra-docs.test
DB_DATABASE=thinkra_docs
DB_USERNAME=root
DB_PASSWORD=

SUPER_ADMIN_EMAIL=admin@thinkra.test
SUPER_ADMIN_PASSWORD=ChangeMe123!
```

### 4. مفتاح التطبيق والجداول

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

### 5. Laragon — Virtual Host

- Document Root: `...\thinkra-docs\public`
- URL مثال: `http://thinkra-docs.test`

أو من مجلد المشروع:

```bash
php artisan serve
```

ثم افتح: `http://127.0.0.1:8000`

## أول Super Admin

يُنشأ تلقائياً عبر `AdminUserSeeder` من متغيرات `.env`:

| المتغير | الافتراضي |
|---------|-----------|
| `SUPER_ADMIN_EMAIL` | admin@thinkra.test |
| `SUPER_ADMIN_PASSWORD` | ChangeMe123! |

**غيّر كلمة المرور فوراً بعد أول دخول.**

## الروابط

| الدور | الرابط |
|-------|--------|
| الأستاذ | `/login` |
| الإدارة | `/admin/login` |

## بيانات تجريبية (محلي فقط)

بعد `migrate --seed` في بيئة `local`:

| الحقل | القيمة |
|-------|--------|
| هاتف الأستاذ | `0500000001` |
| كلمة المرور | `teacher123` |

## هيكل الصلاحيات

### أدوار النظام (`users.role`)

- `SUPER_ADMIN` — كامل الصلاحيات
- `ADMIN` — إدارة الأساتذة والكورسات
- `TEACHER` — كتابة المحتوى حسب عضوية الكورس

### عضوية الكورس (`course_members.role`)

- `OWNER` — تحكم كامل داخل الكورس (فصول + محتوى)
- `EDITOR` — تعديل محتوى الدروس فقط
- `VIEWER` — مشاهدة فقط

### حالة الدرس

`DRAFT` → `NEEDS_REVIEW` → `READY` → `PUBLISHED`

## الأوامر المفيدة

```bash
php artisan migrate:fresh --seed   # إعادة بناء DB (تطوير فقط)
php artisan config:clear
php artisan route:list
```

## الاختبار السريع

1. ادخل `/admin/login` بالسوبر أدمن من `.env`
2. أنشئ أستاذاً واربطه بكورس
3. ادخل `/login` برقم هاتف الأستاذ
4. افتح الكورس → أضف فصلًا ودرسًا → احفظ المحتوى
5. جرّب دخول أستاذ آخر على كورس غير مرتبط → يجب أن يظهر **404**

## البنية (Modular)

```
app/
  Enums/          # UserRole, CourseMemberRole, LessonStatus
  Models/         # User, Course, Chapter, Lesson
  Services/       # CourseAccessService
  Policies/       # CoursePolicy, LessonPolicy
  Http/
    Middleware/   # EnsureAdmin, EnsureTeacher
    Controllers/
      Admin/      # لوحة الإدارة
      Teacher/    # بوابة الأستاذ
      Auth/
database/migrations/
database/seeders/
resources/views/
```

## cPanel

راجع [DEPLOYMENT_CPANEL_GIT.md](DEPLOYMENT_CPANEL_GIT.md)

## التقنيات

- Laravel 11 + MySQL
- Blade + Tailwind (CDN) + Alpine.js (CDN)
- بدون Docker — مناسب لـ Shared Hosting

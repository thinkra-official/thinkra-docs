# نشر Thinkra Docs على cPanel عبر Git Version Control

دليل نشر المشروع على استضافة مشتركة (Shared Hosting) بدون Docker أو صلاحيات root.

## المتطلبات على الاستضافة

- PHP 8.3+ (من MultiPHP Manager)
- MySQL 8+
- Composer (Terminal في cPanel أو SSH إن وُجد)
- Git Version Control في cPanel
- امتدادات PHP: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`

---

## 1. ربط Git repository داخل cPanel

1. افتح **cPanel → Git Version Control**
2. **Create** مستودع جديد أو **Clone** من GitHub/GitLab
3. مسار الاستنساخ المقترح: `~/thinkra-docs` (خارج `public_html` إن أمكن)

مثال Clone:

```
Repository URL: https://github.com/your-org/thinkra-docs.git
Repository Path: thinkra-docs
```

---

## 2. Clone المشروع

بعد Clone، ادخل للمجلد من Terminal:

```bash
cd ~/thinkra-docs
```

---

## 3. ضبط Document Root على مجلد `public`

**الطريقة الموصى بها:** إنشاء subdomain أو domain يشير إلى:

```
/home/username/thinkra-docs/public
```

من **Domains → Domains** أو **Subdomains**:

- Document Root: `thinkra-docs/public` (وليس جذر المشروع)

**بديل:** إذا كان الإجبار على `public_html` فقط:

```bash
# من ~/public_html — أنشئ symlink (إن سمحت الاستضافة)
ln -s ~/thinkra-docs/public ~/public_html/thinkra-docs
```

أو انسخ محتويات `public/` مع تعديل مسار `index.php` (غير مفضل).

---

## 4. إنشاء MySQL Database من cPanel

1. **MySQL® Databases**
2. أنشئ قاعدة: `username_thinkra`
3. أنشئ مستخدمًا بكلمة مرور قوية
4. اربط المستخدم بقاعدة البيانات — **ALL PRIVILEGES**

---

## 5. تعديل ملف `.env`

```bash
cd ~/thinkra-docs
cp .env.example .env
nano .env   # أو File Manager
```

```env
APP_NAME="Thinkra Docs"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://docs.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_thinkra
DB_USERNAME=username_dbuser
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

SUPER_ADMIN_EMAIL=admin@yourdomain.com
SUPER_ADMIN_PASSWORD=StrongPasswordHere!
```

> **مهم:** `APP_DEBUG=false` في الإنتاج.

---

## 6. أوامر النشر

من Terminal داخل مجلد المشروع:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

إذا لم يتوفر Composer في Terminal، نفّذ `composer install` محلياً وارفع مجلد `vendor/` (أبطأ، لكنه يعمل على بعض الاستضافات).

---

## 7. ضبط صلاحيات المجلدات

من File Manager أو Terminal:

```bash
chmod -R 775 storage bootstrap/cache
```

تأكد أن مالك الملفات هو مستخدم PHP/cPanel (غالباً `username`).

| المجلد | الصلاحية |
|--------|----------|
| `storage/` | قابل للكتابة |
| `bootstrap/cache/` | قابل للكتابة |

---

## 8. تحديث المشروع لاحقاً (Git Pull)

1. **Git Version Control → Manage → Pull or Deploy**
2. أو Terminal:

```bash
cd ~/thinkra-docs
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan optimize:clear
php artisan view:cache
```

بعد تحديث واجهة القارئ (CSS/JS) إذا ظهرت نسخة قديمة على iPhone:

1. تأكد أن `git pull` نجح وأن ملفات `public/css/docs-reader.css` و`public/js/docs-reader.js` محدّثة.
2. نفّذ `php artisan view:clear` (القوالب تُخزَّن مؤقتاً).
3. اختياري: في `.env` ضع `APP_ASSET_VERSION=20260604` (أي رقم/تاريخ جديد) ثم `php artisan config:cache` لإجبار كل المتصفحات على تحميل الأصول من جديد.
4. على iPhone: Safari → مسح ذاكرة التخزين المؤقت، أو أغلق التبويب وافتح الرابط من جديد (ليس من سجل «الأخيرة» فقط).
5. في Network على الجوال تحقق أن `docs-reader.css` يحتوي `?v=` برقم حديث.

عند تغيير `.env` فقط:

```bash
php artisan config:clear
php artisan config:cache
```

---

## 9. ملاحظات مهمة

| الموضوع | التوصية |
|---------|---------|
| `.env` | لا ترفعه إلى Git — موجود في `.gitignore` |
| `APP_KEY` | أنشئه مرة واحدة على السيرفر بـ `key:generate` |
| الجلسات | `SESSION_DRIVER=database` — يعمل بدون Redis |
| Tailwind / Alpine | عبر CDN — لا يحتاج `npm run build` على السيرفر |
| Cron | غير مطلوب للوظائف الأساسية حالياً |
| Queue Worker | اختياري — الافتراضي `database` |

### استكشاف الأخطاء

- **500 Error:** راجع `storage/logs/laravel.log`
- **صفحة بيضاء:** فعّل `APP_DEBUG=true` مؤقتاً ثم أعد `false`
- **CSRF / Session:** تأكد من `APP_URL` يطابق الدومين الفعلي مع `https://`

---

## قائمة تحقق بعد النشر

- [ ] `/admin/login` يعمل
- [ ] Super Admin من `.env` يدخل بنجاح
- [ ] إنشاء أستاذ وكورس من لوحة الإدارة
- [ ] `/login` للأستاذ يعمل
- [ ] أستاذ لا يرى كورسات غير مرتبطة (404)
- [ ] `storage` و `bootstrap/cache` قابلان للكتابة

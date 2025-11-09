# استخدم PHP CLI لتشغيل php artisan serve بسهولة
FROM php:8.3-cli

# تثبيت أدوات نظامية مطلوبة
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libicu-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# تثبيت امتدادات PHP شائعة للـ Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring zip exif pcntl bcmath sockets gd intl

# انسخ Composer من الصورة الرسمية
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# اضبط مجلد العمل
WORKDIR /var/www/html

# افحص صلاحيات composer cache (اختياري)
RUN useradd -G www-data,root -u 1000 -m developer

# أمر افتراضي (يمكن تغييره) — سنشغّل artisan من داخل الكونتينر
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

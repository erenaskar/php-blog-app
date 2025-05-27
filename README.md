# Modern PHP Blog Application

Modern bir PHP blog uygulaması. Bu proje, PHP 8.1+, MySQL ve modern web teknolojileri kullanılarak geliştirilmiştir.

## Özellikler

- Kullanıcı kayıt ve giriş sistemi
- Blog yazıları CRUD işlemleri
- Yorum sistemi
- Responsive tasarım (Bootstrap/Tailwind CSS)
- Güvenlik önlemleri (CSRF, SQL Injection koruması)
- Birim testleri
- Docker desteği

## Gereksinimler

- PHP 8.1 veya üzeri
- MySQL 8.0 veya üzeri
- Composer
- Docker (opsiyonel)

## Kurulum

1. Projeyi klonlayın:
```bash
git clone [repo-url]
cd phpblog
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. `.env` dosyasını oluşturun:
```bash
cp .env.example .env
```

4. Veritabanını oluşturun:
```bash
mysql -u root -p < database/schema.sql
```

5. Docker ile çalıştırmak için:
```bash
docker-compose up -d
```

## Geliştirme

- `composer test` - Birim testlerini çalıştırır
- `composer cs-check` - Kod stilini kontrol eder
- `composer cs-fix` - Kod stilini düzeltir

## Lisans

MIT 
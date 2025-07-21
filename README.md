# Takip Sistemi

Bu proje, domain ve hosting hizmetlerini takip etmek için basit bir PHP panelidir.

## Kurulum

1. `sql/schema.sql` dosyasındaki tabloları MySQL veritabanınıza aktarın.
2. `config/config.php` dosyasında yer alan veritabanı ve SMTP bilgilerini gerekirse güncelleyin.
3. Depo kök dizinini web sunucunuzun kök dizini olarak ayarlayın.

## Sayfalar

- `/dashboard.php` – Özet panel
- `/customers.php` – Müşteri listesi
- `/customer_add.php` – Müşteri ekleme formu
- `/services.php` – Hizmet listesi
- `/service_add.php` – Hizmet ekleme formu
- `/exchange_rates_cron.php` – Günlük kur çekme işlemi

Tüm arayüz Türkçe olup Bootstrap 5 kullanılarak oluşturulmuştur.

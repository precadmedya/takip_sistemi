# Takip Sistemi

Bu proje, domain ve hosting hizmetlerini takip etmek için basit bir PHP panelidir.

## Kurulum

1. `sql/schema.sql` dosyasındaki tabloları MySQL veritabanınıza aktarın.
2. `config/config.php` dosyasında yer alan veritabanı ve SMTP bilgilerini gerekirse güncelleyin.
3. Depo kök dizinini web sunucunuzun kök dizini olarak ayarlayın.

İlk giriş için veritabanına varsayılan bir kullanıcı eklenmiştir:

- E‑posta: `info@precadmedya.com.tr`
- Şifre: `123456`

## Sayfalar

- `/login.php` – Oturum açma ekranı
- `/dashboard.php` – Özet panel
- `/customers.php` – Müşteri listesi
- `/customer_payment.php` – Müşteri tahsilatı
- `/customer.php` – Müşteri detayları ve hizmet listesi
- `/customer_add.php` – Müşteri ekleme formu
 - `/services.php` – Hizmet listesi
 - `/service_payment.php` – Hizmet tahsilatı ve yenileme
 - `/service_add.php` – Hizmet ekleme formu (başlangıç ve ödeme tarihleri seçilebilir)
 - `/service.php` – Hizmet detayları
- `/products.php` – Ürün yönetimi
- `/providers.php` – Sağlayıcı yönetimi
- `/users.php` – Kullanıcı yönetimi
- `/settings.php` – Logo ve boyut ayarları
- `/exchange_rates_cron.php` – Günlük kur çekme işlemi

Müşteri listesi sayfasında her müşterinin TL cinsinden bakiyesi görüntülenir. USD olarak kaydedilmiş hizmet bedelleri, sistemdeki en güncel kura göre TL'ye dönüştürülerek hesaplanır. Tahsilatlar hem müşteri hem de hizmet bazında kaydedilir ve bakiye bu ödemeler düşülerek hesaplanır.

Tüm arayüz Türkçe olup Bootstrap 5 kullanılarak oluşturulmuştur. Sayfalara erişmek için oturum açmak gereklidir.
Logo yükleme sayfasında giriş ve üst menüde kullanılacak logonun boyutları ayarlanabilir.

Veritabanında `payments` tablosu tahsilat kayıtlarını tutar ve `exchange_rates` tablosundaki güncel dolar kuru kullanılarak USD tahsilatları otomatik TL'ye çevrilir.
Hizmet kayıtlarında hem orijinal para birimi hem de TL karşılığı saklanır ve ödeme tarihi alanı bulunur.

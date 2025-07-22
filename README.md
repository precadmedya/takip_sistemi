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
- `/dashboard.php` – Anasayfa, iki sütunlu takvim ve yaklaşan hizmet listesi
- `/customers.php` – Müşteri listesi
 - `/customer_payment.php` – Müşteri tahsilatı (isteğe bağlı hizmet seçilebilir)
- `/customer.php` – Müşterinin detaylı sayfası, geçmiş ödemeler ve yaklaşan borçlar
- `/customer_statement.php` – Müşteri ekstresi (CSV indirme)
 - `/customer_add.php` – Müşteri ekleme formu
 - `/customer_edit.php` – Müşteri düzenleme
 - `/customer_delete.php` – Müşteri silme
 - `/services.php` – Hizmet listesi
 - `/service_payment.php` – Hizmet tahsilatı ve yenileme
   (mevcut borcu görüntüler ve ödeme sonrası uzatma seçeneği sunar)
 - `/service_add.php` – Hizmet ekleme formu. Ürün satırında seçim yapıldığında fiyat, döviz ve KDV otomatik dolar.
   Listede olmayan ürünler eklenmek istendiğinde form, ürünü ürünler sayfasına kaydetmeyi teklif eder. Satırlardan elde edilen toplam otomatik hesaplanır.
 - `/service_edit.php` – Hizmet düzenleme
 - `/service_delete.php` – Hizmet silme
 - `/service.php` – Hizmet detayları ve tahsilat geçmişi
- `/payment_edit.php` – Tahsilat düzenleme
- `/payment_delete.php` – Tahsilat silme
- `/products.php` – Ürün yönetimi
- `/providers.php` – Sağlayıcı yönetimi
- `/users.php` – Kullanıcı yönetimi
- `/settings.php` – Logo ve boyut ayarları
- `/exchange_rates_cron.php` – Günlük kur çekme işlemi
- `/exchange_rates.php` – Kur geçmişi listesi

`exchange_rates_cron.php` dosyası her gün çalıştırılarak TCMB'den USD kurunun
güncel değerini `exchange_rates` tablosuna kaydeder. Cron örneği:

```
0 9 * * * php /path/to/exchange_rates_cron.php >/dev/null 2>&1
```

Müşteri listesi sayfasında her müşterinin TL cinsinden bakiyesi görüntülenir. USD olarak kaydedilmiş hizmet bedelleri, sistemdeki en güncel kura göre TL'ye dönüştürülerek hesaplanır. Tahsilatlar hem müşteri hem de hizmet bazında kaydedilir ve bakiye bu ödemeler düşülerek hesaplanır.

Tüm arayüz Türkçe olup Bootstrap 5 kullanılarak oluşturulmuştur. Sayfalara erişmek için oturum açmak gereklidir.
Logo yükleme sayfasında giriş ve üst menüde kullanılacak logonun boyutları ayarlanabilir.

Veritabanında `payments` tablosu tahsilat kayıtlarını tutar ve `exchange_rates` tablosundaki güncel dolar kuru kullanılarak USD tahsilatları otomatik TL'ye çevrilir.
Her hizmet için ek kalemlerin saklandığı `service_items` tablosu da bulunmaktadır. Bu tabloda artık her kalemin döviz türü ve sağlayıcısı da saklanır.
Hizmet kayıtlarında hem orijinal para birimi hem de TL karşılığı saklanır ve ödeme tarihi alanı bulunur.

Dashboard sayfasında aylık görünümlü bir takvim ile yaklaşan hizmet bitişleri aynı sayfada iki sütun olarak gösterilir. Takvimde hizmet tarihi olan günler renkli çubuklarla işaretlenir ve tıklandığında o güne ait hizmetler modal pencerede açılır. Sağdaki listede en yakın on hizmet bitişi arama kutusuyla filtrelenebilir. Alt bölümde en çok satan ve son eklenen hizmetler yer alır.

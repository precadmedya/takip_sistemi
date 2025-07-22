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
- `/customer_statement.php` – Müşteri ekstresi (PDF indirme)
 - `/customer_add.php` – Müşteri ekleme formu
 - `/customer_edit.php` – Müşteri düzenleme
 - `/customer_delete.php` – Müşteri silme
 - `/services.php` – Hizmet listesi
 - `/service_payment.php` – Hizmet tahsilatı ve yenileme
   (mevcut borcu görüntüler ve ödeme sonrası uzatma seçeneği sunar)
 - `/service_add.php` – Hizmet ekleme formu. Ürün satırında seçim yapıldığında fiyat, döviz ve KDV otomatik dolar.
  Satırlarda "Açıklama" alanı bulunur ve "+ Özel Ürün" seçildiğinde bilgiler doğrudan satırdan yazılarak kaydedilebilir. İstenirse bu ürünler otomatik olarak ürünler listesine eklenir. İsteğe bağlı olarak belirli günlerde hatırlatma e-postası gönderilmesi için seçenekler vardır.
 - `/service_edit.php` – Hizmet düzenleme
 - `/service_delete.php` – Hizmet silme
- `/service.php` – Hizmet detayları, ek kalem tablosu ve tahsilat geçmişi
- `/send_reminder.php?service_id=X` – Manuel hatırlatma maili gönderir
- `/payment_edit.php` – Tahsilat düzenleme
- `/payment_delete.php` – Tahsilat silme
- `/products.php` – Ürün yönetimi
- `/providers.php` – Sağlayıcı yönetimi (borç tutarlarını gösterir, detay sayfasından satın alımlar listelenebilir)
- `/provider.php?id=X` – Tedarikçi detay sayfası ve yeni satın alım girişi
- `/purchase_edit.php?id=X` – Satın alınan ürünü düzenleme
- `/purchase_delete.php?id=X` – Satın alınan ürünü silme
 - `/provider_payment.php?provider_id=X` – Tedarikçiye ödeme kaydetme
 - `/provider_payment_edit.php?id=X` – Tedarikçi ödemesini düzenleme
 - `/provider_payment_delete.php?id=X` – Tedarikçi ödemesini silme
- `/purchase_add.php` – Sağlayıcı seçerek ürün satın alma formu
- `/users.php` – Kullanıcı yönetimi
 - `/settings.php` – Logo ve SMTP ayarları
- `/update_rates.php` – TCMB kurunu çekip hizmet toplamlarını güncelleme (header'daki "Kur Güncelle" butonuyla erişilir)
- `/exchange_rates_cron.php` – Günlük kur çekme işlemi
- `/reminder_cron.php` – Hatırlatma maillerini otomatik gönderir
- `/exchange_rates.php` – Kur geçmişi listesi

`exchange_rates_cron.php` dosyası her gün çalıştırılarak TCMB'den USD kurunun
güncel değerini `exchange_rates` tablosuna kaydeder. Cron örneği:

```
0 9 * * * php /path/to/exchange_rates_cron.php >/dev/null 2>&1
```

Hatırlatma maillerini göndermek için benzer şekilde `reminder_cron.php` dosyası günlük çalıştırılabilir:

```
0 8 * * * php /path/to/reminder_cron.php >/dev/null 2>&1
```

SMTP ayarları `settings.php` sayfasından değiştirilebilir. Buradaki bilgiler
`config/config.php` dosyasındaki varsayılan ayarların üzerine yazılır ve mail
gönderiminde kullanılır. Ayarlar ekranında test e-postası gönderebileceğiniz bir bölüm de bulunur.

Müşteri listesi sayfasında her müşterinin TL cinsinden bakiyesi görüntülenir. USD olarak kaydedilmiş hizmet bedelleri, sistemdeki en güncel kura göre TL'ye dönüştürülerek hesaplanır. Tahsilatlar hem müşteri hem de hizmet bazında kaydedilir ve bakiye bu ödemeler düşülerek hesaplanır.

Tüm arayüz Türkçe olup Bootstrap 5 kullanılarak oluşturulmuştur. Sayfalara erişmek için oturum açmak gereklidir.
Logo yükleme sayfasında giriş ve üst menüde kullanılacak logonun boyutları ayarlanabilir.

Veritabanında `payments` tablosu tahsilat kayıtlarını tutar ve `exchange_rates` tablosundaki güncel dolar kuru kullanılarak USD tahsilatları otomatik TL'ye çevrilir.
Her hizmet için ek kalemlerin saklandığı `service_items` tablosu da bulunmaktadır. Bu tabloda her kalemin döviz türü, sağlayıcı bilgisi ve açıklaması saklanır.
Hizmet kayıtlarında hem orijinal para birimi hem de TL karşılığı saklanır ve ödeme tarihi alanı bulunur.
Tedarikçilerden yapılan alımlar `provider_purchases` tablosuna kaydedilir ve toplam tutarlar sağlayıcı listesinde görüntülenir.
Sağlayıcı ödemeleri `provider_payments` tablosunda tutulur; ödemeler eklenebilir, düzenlenebilir ve silinebilir.
Alım kayıtları düzenlenip silinebilir.
Dashboard sayfasındaki takvimde müşteriler ve sağlayıcılar için ayrı sekmeler bulunur. Sağlayıcı takvimi ödeme tarihlerini gösterir ve son 30 gün içindeki tutar kart olarak listelenir.

Sağlayıcı eklerken web sitesi de kaydedilebilir. Sağlayıcı detay sayfasında ürün adı, miktar, birim, birim fiyat, döviz ve KDV oranı girilerek satın alımlar eklenir. Girilen değerler güncel USD kuru kullanılarak TL'ye çevrilir ve satırın altında birim fiyat, KDV tutarı ve toplam tutar olarak gösterilir. Sağlayıcı listesi her sağlayıcı için biriken toplam ödemeyi TL cinsinden gösterir.

`update_rates.php` sayfasıyla TCMB'den güncel USD kuru çekilerek tüm hizmet toplamları yeniden hesaplanabilir. Güncel kur bilgisi menüde gösterilir ve yanındaki renkli butonla güncellenebilir. Hizmet detayı sayfasında kalan gün bir rozet olarak görünür ve alt kısımda birim fiyat, KDV tutarı ve genel toplam TL olarak listelenir. Müşteri ekstresi PDF formatında logo ile birlikte indirilebilir.

Dashboard sayfasında aylık görünümlü bir takvim ile yaklaşan hizmet bitişleri aynı sayfada iki sütun olarak gösterilir. Takvimde hizmet tarihi olan günler renkli çubuklarla işaretlenir ve tıklandığında o güne ait hizmetler modal pencerede açılır. Sağdaki listede en yakın on hizmet bitişi arama kutusuyla filtrelenebilir. Alt bölümde en çok satan ve son eklenen hizmetler yer alır.

Gönderilen hatırlatma e-postaları `email_logs` tablosunda saklanır.

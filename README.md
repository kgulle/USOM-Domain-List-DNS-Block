# USOM Zararlı Bağlantı Listesini Bind DNS Servisinde Engelleme.

PHP ile geliştirdiğimiz uygulama ile USOM zararlı bağlantı listesindeki(https://www.usom.gov.tr/zararli-baglantilar/1.html) domain adreslerini istediğimiz periyotlarla DNS sunucumuzda engelliyoruz.

# KURULUM

1. DNS sunucumuzda **/etc/bind** klasörüne **blockeddomains.db** dosyasını ekliyoruz. (Bu dosyadaki 127.0.0.1 adresi engellenen domain adreslerinin hangi ip adresine yönlendireceğimiz bilgisidir.)

2. Dns sunucumuzda **named.conf.local** dosyasının en üstüne include **"/etc/bind/blacklisted.zones";** satırını ekliyoruz. ve DNS sunucumuzda işlemimiz bitiyor. İkinci DNS sunucumuzdada yukarıdaki işlemleri yapmamız lazım. Mevcut betik iki dns sunucu için yazılmıştır. Bir DNS veya 2 den fazla DNS sunucu için kodda basit değişkilik yapmanız gerekir.

NOT. isterseniz php ile yazılan betiği kullanmayıp **blacklisted.zones** dosyasına engellemek istediğiniz domain adreslerini 
*zone "vakifbank-tr.com" {type master; file "/etc/bind/blockeddomains.db";};*
formatında ekleyerek engelleyebiliriz.

3. Şimdi PHP kodlarını web serverimiza atıp onları belirli süreler ile çalıştırmaktan ibaret. 
Bu süreç değişen zararlı bağlantı listesini blacklisted.zones dosyasına uygun formatta yazıp dns sunucularımıza gönderip DNS servisini yeniden başlatıp DNS servisimizin status çıktısı *SUCCESS* ise ilgili kişiye sms gondermesi. 
NOT. bazı bind sunucularda service bind status çıktısı *running* olarak çıktı veriyor eğer sizde de öyle ise kod da success ola yerleri *running* olarak değiştirmemiz gerekmektedir.

4. Windows web sunucularda kodumuzun istediğimiz periyotlarda çalışması için görev zamanlayıcı oluşturup index.php dosyasını çalıştırmamız lazım. bunun için ben .bat dosyası oluşturup görev zamanlıyıcıda .bat dosyasını çaliştirarak yaptım. Linux makinelerde crontab yazarak daha basit şekilde yapabiliriz.

Destek için lütfen kenan.gulle@dpu.edu.tr,kenangulle@hotmail.com yazabilirsiniz.


# ÇALIŞMA MANTIĞI

1. Usom zararlı bağlantılardaki eklenen son kaydın ID değerini sonkayit dosyamızdan okuyoruz, usom zararlı bağlantılar listesindeki eklenen son kaydın ID değeri ile karşılaştırıp büyük ise; ikinci adım, değil ise bir şey yapmıyor program sonlanıyor.(Engellenecek yeni kayıt olmadığı için)

2. Usom zararlı bağlantılar listesindeki kayıtların içinde sadece domain adreslerini almak için ip adreslerini önemsemiyoruz. yani domain adreslerini sadece alıyoruz.

...

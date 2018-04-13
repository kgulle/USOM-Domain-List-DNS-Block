<?php
//chdir('C:\inetpub\wwwroot\Dns_Usom');
require_once ('Net/SSH2.php');
require_once ('Net/SFTP.php');
require_once ('classusom.php');

$server1 = "194.27.4.17";
$username1 = "root";
$password1 = "pass";

$server2 = "194.27.4.172";
$username2 = "root";
$password2 = "pass";

$ssh1 = new Net_SSH2($server1);
$sftp1 = new Net_SFTP($server1);

$ssh2 = new Net_SSH2($server2);
$sftp2 = new Net_SFTP($server2);
$Usom = new Usom;

if (!$ssh1->login($username1, $password1)) exit('Net_SSH2 baglantisi yapılamadı.');
if (!$sftp1->login($username1, $password1)) exit('Net_SFTP baglantisi yapılamadı.');
if (!$ssh2->login($username2, $password2)) exit('Net_SSH2 baglantisi yapılamadı.');
if (!$sftp2->login($username2, $password2)) exit('Net_SFTP baglantisi yapılamadı.');

// kopyalama islemi basarili ise cikti gondermiyor. basarisiz ise cikti veriyor.
// cikti var ise hata olustu.
if (strlen($sonuc1) > 1) die("hata olustu");
if (strlen($sonuc2) > 1) die("hata olustu");

// buraya geldi ise derleyici, hata yoktur. islemlere devam ediyorum
// usomdan son kayıt tarihini ve son kayıt ID sini alıyorum
$usom_data = $Usom->Usom_Son_Id_Tarih_Oku();
$usom_son_id = $usom_data[0];
$usom_son_guncelleme = $usom_data[1];
echo "Son güncelleme tarihi:" . $usom_son_guncelleme . "<br />";

// usomdan gelen son id dosya ya yazdığımız son id den buyuk ise yeni domain bloklanmıştır.
if ($usom_son_id > $Usom->Dosya_Son_Id_Oku())
{
	$sonuc1 = $ssh1->exec("cp /etc/bind/blacklisted.zones /etc/bind/blacklisted.zones_Yedek");
	$sonuc2 = $ssh2->exec("cp /etc/bind/blacklisted.zones /etc/bind/blacklisted.zones_Yedek");

	// usom xml dosyasındaki kayıtları blacklisted.zones dosyasına uygun formatta yazıyorum.
	echo $sonuc_parser = $Usom->Usom_Url_Parser();

	// usom xml deki son kayıdın id sini, sonkayit adli dosyaya yazıyorum.
	// yeni kayıt gelip gelmediğini daha sonra bu id değerine bakarak anlıcam
	// (tarihe bakarakta anlayabilirdik)
	$Usom->Dosya_Son_Id_Yaz($usom_son_id);

	// olusan dosyayı sftp ile dns sunucuya transfer ediyorum
	$sftp1->put('/etc/bind/blacklisted.zones', 'blacklisted.zones', NET_SFTP_LOCAL_FILE);
	$sftp2->put('/etc/bind/blacklisted.zones', 'blacklisted.zones', NET_SFTP_LOCAL_FILE);
}
else
{
	// usom xml deki son kayıt id si sonkayit adlı dosyadakinden büyük değilse
	// yeni kayit olmadıgı anlamına geliyor.
	// $Usom->SMS_Gonder("05423322504", "Yeni engellenecek adres yok.\nson güncel kayıt tarihi:" . $usom_son_guncelleme, "normal");
	die("Yeni engellenecek kayıt yok.");
}

// dosyayı dns sunucuya gonderdikten sonra services i restart yapıyoruz

echo $sonuc1 = $ssh1->exec("service bind9 restart") . "<br />";
echo $sonuc2 = $ssh2->exec("service bind9 restart") . "<br />";

// 10 saniye bekliyoruz servis oturması için.
sleep(10);
echo $sonuc1 = $ssh1->exec("service bind9 status") . "<br />";
echo $sonuc2 = $ssh2->exec("service bind9 status") . "<br />";

// sonucta SUCCESS var mı diye kontrol ediyoruz. var ise servis calistigini gosteriyor,
if (strpos($sonuc1, 'SUCCESS') !== false)
{
	$sonuc1=true;
	//$Usom->SMS_Gonder("05423322504", "Güncel URL listesi alındı.\n$server1 DNS servisi başarıyla Çalıştırıldı.\nSon kayıt:" . $usom_son_guncelleme . "\n" . $sonuc_parser, "normal");
}
else
{
	// yok ise zones_Yedek dosyasını geri yukleyip servisi restart ediyoruz
	echo $sonuc1 = $ssh1->exec("cp /etc/bind/blacklisted.zones_Yedek /etc/bind/blacklisted.zones");
	echo $sonuc1 = $ssh1->exec("service bind9 restart") . "<br />";
	sleep(10);
	echo $sonuc1 = $ssh1->exec("service bind9 status") . "<br />";
	if (strpos($sonuc1, 'SUCCESS') !== false) $sonuc1=true;
	else
	{
		$Usom->SMS_Gonder("05423322504", $server1." ACIL DURUM Servis çalışmıyor!", "normal");
		echo "mesaj gonder servis calismiyor";
		$sonuc1=false;
	}
}
// ikinci dns servisimiz için işlemleri yapıyoruz.
if (strpos($sonuc2, 'SUCCESS') !== false)
{
	$sonuc2=true;
	//$Usom->SMS_Gonder("05423322504", "Güncel URL listesi alındı.\n$server2 DNS servisi başarıyla Çalıştırıldı.\nSon kayıt:" . $usom_son_guncelleme . "\n" . $sonuc_parser, "normal");
}
else
{
	// yok ise zones_Yedek dosyasını geri yukleyip servisi restart ediyoruz
	echo $sonuc2 = $ssh2->exec("cp /etc/bind/blacklisted.zones_Yedek /etc/bind/blacklisted.zones");
	echo $sonuc2 = $ssh2->exec("service bind9 restart") . "<br />";
	sleep(10);
	echo $sonuc2 = $ssh2->exec("service bind9 status") . "<br />";
	if (strpos($sonuc2, 'SUCCESS') !== false) $sonuc2=true;
	else
	{
		$Usom->SMS_Gonder("05423322504",$server2. " ACIL DURUM Servis çalışmıyor!", "normal");
		echo "mesaj gonder servis calismiyor";
		$sonuc2=false;
	}
}


if ($sonuc1==true AND $sonuc2==true) 
	$Usom->SMS_Gonder("05423322504", "Güncel URL listesi alındı.\n$server1 ve $server2 DNS servisi başarıyla Çalıştırıldı.\nSon kayıt:" . $usom_son_guncelleme . "\n" . $sonuc_parser, "normal");
	else if($sonuc1==true) $Usom->SMS_Gonder("05423322504", "Güncel URL listesi alındı.\n$server1 DNS servisi başarıyla Çalıştırıldı.\nSon kayıt:" . $usom_son_guncelleme . "\n" . $sonuc_parser, "normal");
		else if($sonuc2==true)
			$Usom->SMS_Gonder("05423322504", "Güncel URL listesi alındı.\n$server2 DNS servisi başarıyla Çalıştırıldı.\nSon kayıt:" . $usom_son_guncelleme . "\n" . $sonuc_parser, "normal");

?>

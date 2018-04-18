<?php
class Usom
{
	static $izinver_dizi = array();
	static $engelle_dizi = array();
	static $usom_dizi = array();
	static $dosya_ad = "sonkayit";
	static $usom_xml_url = "https://www.usom.gov.tr/url-list.xml";
	static $zonefile = "blacklisted.zones";
	function SMS_Gonder($telefon, $mesaj, $tip)
	{
		$url = "http://192.168.55.215:8080/kalkun/test2.php?telefon=" . $telefon . "&mesaj=USOM" . urlencode("\n" . $mesaj) . "&tip=" . $tip;
		echo "<br />" . $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
	}

	function Dosyadan_Oku_Engelle()
	{
		$dosya = fopen("engelle.txt", "r");
		if ($dosya) {
			while (($line = fgets($dosya)) !== false) {
				$url = $line;
				$url = preg_replace('/\s+/', '', $url); // bosluklar silindi.
				$url = preg_replace("(^https?://)", "", $url);
				$url = preg_replace("(^http?://)", "", $url); //usoma eklenen linklerin bazılarında http tagı olması bazılarında olmaması yuzunden kaldırıp ekliyoruz
				$url = "http://" . $url; //url e cevirdik
				$url = parse_url($url); //url parse yapıyoruz .
				$url = $url['host'];
				array_push(self::$engelle_dizi, $url);
			}

			fclose($dosya);
			self::$engelle_dizi = array_unique(self::$engelle_dizi); //kayıtları benzersiz yapıyoruz
		}
	}

	function Dosyadan_Oku_Izinver()
	{
		$dosya = fopen("izinver.txt", "r");
		if ($dosya) {
			while (($line = fgets($dosya)) !== false) {
				$url = $line;
				$url = preg_replace('/\s+/', '', $url); // bosluklar silindi.
				$url = preg_replace("(^https?://)", "", $url);
				$url = preg_replace("(^http?://)", "", $url); //usoma eklenen linklerin bazılarında http tagı olması bazılarında olmaması yuzunden kaldırıp ekliyoruz
				$url = "http://" . $url; //url e cevirdik
				$url = parse_url($url); //url parse yapıyoruz .
				$url = $url['host'];
				array_push(self::$izinver_dizi, $url);
			}

			fclose($dosya);
			self::$izinver_dizi = array_unique(self::$izinver_dizi); //kayıtları benzersiz yapıyoruz
		}
	}

	function Usom_Oku()
	{
		$xml = simplexml_load_file(self::$usom_xml_url) or die("XML Okunamadi.");
		$x = 0;
		foreach($xml->{'url-list'}->{'url-info'} as $kayitlar) {
			$url = $kayitlar->url;
			$url = preg_replace('/\s+/', '', $url); // bosluklar silindi.
			$url = preg_replace("(^https?://)", "", $url);
			$url = preg_replace("(^http?://)", "", $url); //usoma eklenen linklerin bazılarında http tagı olması bazılarında olmaması yuzunden kaldırıp ekliyoruz
			$url = "http://" . $url; //url e cevirdik
			$url = parse_url($url); //url parse yapıyoruz .
			self::$usom_dizi[$x] = $url['host']; //sadece domian kısmını alıp diziye atıyoruz.
			$x++;
		}
		
		self::$usom_dizi = array_unique(self::$usom_dizi); //kayıtları benzersiz yapıyoruz
	}

	function Zone_Dosyasi_olstur()
	{
		self::$usom_dizi = array_merge(self::$usom_dizi, self::$engelle_dizi); //engellenen dizileri birlestirdik.
		self::$usom_dizi = array_diff(self::$usom_dizi, self::$izinver_dizi); //istisna siteleri siliyoruz
		self::$usom_dizi = array_unique(self::$usom_dizi); //kayıtları benzersiz yapıyoruz
		if (file_exists(self::$zonefile)) { //zonefile yoksa islem yapmiyor
			$dosya = fopen(self::$zonefile, 'w'); //dosyayi aciyoruz
			$say = 0;
			for ($x = 0; $x < count(self::$usom_dizi); $x++) { //arraylist icindeki null,"", ve ip adresi olan kayıtları istemiyoruz.
				if ((self::$usom_dizi[$x] == "") OR (self::$usom_dizi[$x] == null) OR (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', self::$usom_dizi[$x]))) continue;
				fwrite($dosya, 'zone "' . self::$usom_dizi[$x] . '" {type master; file "/etc/bind/blockeddomains.db";};' . "\n");
				$say++;
			}

			fclose($dosya);
			return "Engellenen domain:" . $say;
		}
	}

	function Usom_Son_Id_Tarih_Oku()
	{
		libxml_use_internal_errors(true);
		$xml = simplexml_load_file(self::$usom_xml_url) or die("xml dosyasi okunmadi");
		return array(
			$xml->{'url-list'}->{'url-info'}[0]->id,
			$xml->{'url-list'}->{'url-info'}[0]->date
		);
		// return $xml->{'url-list'}->{'url-info'}[0]->id;
	}

	function Dosya_Son_Id_Oku()
	{
		if (file_exists(self::$dosya_ad)) {
			$dosya = fopen(self::$dosya_ad, 'r');
			$icerik = fread($dosya, filesize(self::$dosya_ad));
			fclose($dosya);
			return $icerik;
		}
		else {
			return 0;
		}
	}

	function Dosya_Son_Id_Yaz($veri)
	{
		if (file_exists(self::$dosya_ad)) {
			$dosya = fopen(self::$dosya_ad, 'w');
			fwrite($dosya, $veri);
			fclose($dosya);
			return 1;
		}
		else {
			return 0;
		}
	}
}

?>

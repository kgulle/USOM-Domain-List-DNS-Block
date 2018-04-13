<?php
class Usom

{
	static $dosya_ad = "sonkayit";
	static $usom_xml_url = "https://www.usom.gov.tr/url-list.xml";
	static $zonefile = "blacklisted.zones";
	static $istisna_siteler = array(
		"google.com",
		"facebook.com",
		"goo.gl",
		"dumlupinar.edu.tr",
		"dpu.edu.tr"
	);
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

	function Usom_Url_Parser()
	{
		$kayitarray = array();
		$xml = simplexml_load_file(self::$usom_xml_url) or die("Error: Cannot create object");
		$x = 0;
		foreach($xml->{'url-list'}->{'url-info'} as $kayitlar) {
			$url = $kayitlar->url;
			$url = preg_replace("(^https?://)", "", $url);
			$url = preg_replace("(^http?://)", "", $url); //usoma eklenen linklerin bazılarında http tagı olması bazılarında olmaması yuzunden kaldırıp ekliyoruz
			$url = "http://" . $url;
			$url = parse_url($url); //url parse yapıyoruz .
			$kayitarray[$x] = $url['host']; //sadece domian kısmını alıp diziye atıyoruz.
			$x++;
		}

		$kayitarray = array_unique($kayitarray); //kayıtları benzersiz yapıyoruz
		$kayitarray = array_diff($kayitarray, self::$istisna_siteler); //istisna siteleri siliyoruz
		if (file_exists(self::$zonefile)) { //zonefile yoksa islem yapmiyor
			$dosya = fopen(self::$zonefile, 'w'); //dosyayi aciyoruz
			$say=0;
			for ($x = 0; $x < count($kayitarray); $x++) { //arraylist icindeki null,"", ve ip adresi olan kayıtları istemiyoruz.
				if (($kayitarray[$x] == "") OR ($kayitarray[$x] == null) OR (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $kayitarray[$x]))) continue;
				fwrite($dosya, 'zone "' . $kayitarray[$x] . '" {type master; file "/etc/bind/blockeddomains.db";};' . "\n");
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
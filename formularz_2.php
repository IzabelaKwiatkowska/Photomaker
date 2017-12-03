<?php
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'image_manipulator.php';

$width = (int)$_POST['width'];
$height = (int)$_POST['height'];

$uploadFiles = 'upload2'; //Nazwa folderu w kt�rym zapisane zostan� zdj�cia u�ytkownika
$converted = 'converted'; //Nazwa folderu w kt�rym zapisane zostan� przetworzone zdj�cia
$zipFiles = 'zip'; //Nazwa folderu w kt�rym zapisane zostan� pliki zip z przetoworzonymi zdj�ciami

//towrzymy powy�sze foldery, jesli ich nie ma
if(!is_dir($uploadFiles)) {
    mkdir($uploadFiles);
}
if(!is_dir($converted)) {
    mkdir($converted);
}
if(!is_dir($zipFiles)) {
    mkdir($zipFiles);
}

$uploadedFilesAll = [];

for ($i=1; $i<6; $i++) {
    
    $key = 'file'.$i;
    
    if (isset($_FILES[$key]) === false){
        continue;
    }
    
    $plik_tmp = $_FILES[$key]['tmp_name'];
    $plik_nazwa = $_FILES[$key]['name']; //nazwa pliku (taka jak na komputerze uzytkownika)
    $link = $uploadFiles. '/' .$plik_nazwa; //sciezka do pliku na serwerze - tam zapisujemy plik
	
    if(is_uploaded_file($plik_tmp) && strlen($plik_nazwa) > 0) { 
        
		move_uploaded_file($plik_tmp, $link); 
		
		//dodajemy do tablicy informacje o kazdym zapisanym pliku
		$uploadedFilesAll[] = [
			'user_name' => $plik_nazwa,
			'user_name_path' => $link, 
			'temp_name' => time() . rand(100, 999) . '.jpg' //nazwa losowa dla przekonwertowanego pliku
		];
		
	}
}

//Tworzymu obiekt reprezentujacy nasz plik ZIP
$zip = new ZipArchive();

//nazwa pliku zip, do kt�rego dodamy przetworzone zdj�cia
$filenameZip = $zipFiles . '/photomaker_'.time().'.zip';

// jesli wystapi� jaki� b��dy przy tworzeniu zipa ko�czymy skrypt i wy�wietlamy komunikat
if ($zip->open($filenameZip, ZipArchive::CREATE) !== TRUE) {
    exit("cannot open <$filenameZip>\n");
}

//uruchamiamy p�tle - przetwarzamy ka�dy przes�any plik
foreach($uploadedFilesAll as $file) {
	
	$fileUserName = $file['user_name'];
	$filePath = $file['user_name_path'];
	$fileTempName = $file['temp_name'];
	$fileTempPath = $converted . '/' . $fileTempName; //sciazka przekonwertowanego pliku
	
	//img obiekt reprezentuj�cy przetworzony plik
    $img = new ImageManipulator($filePath); //tworzymy obiekt i podajemy sciezke do oryginalnego pliku
    $img->resampleAndCrop($width, $height); //przetwarzamy
    $img->save($fileTempPath); //zapisujemy przetworzony plik
	
	//przetworzoiny dodajmy do pliku zip
	//$fileTempPath - to �cie�ka do pliku na serwerze, kt�ry ma zosta� dodany do pliku ZIP
	//$fileUserName - to nazwa pliku - plik w ZIPe b�dzie pod t� nazw�
	$zip->addFile($fileTempPath, $fileUserName);
	
}
//zamykamy plik zip - nie mozna juz wi�cej doda� tam plik�w
$zip->close();

//funkcja header - ustawia informacje dla przegl�darki
//w tym przypadku dajemy info �e przegl�darka ma pobra� plik zip
header('Content-Type: application/zip');
header('Content-disposition: attachment; filename=photomaker.zip');
header('Content-Length: ' . filesize($filenameZip));

//odczytujemy plik zip i wysy�amy do przegl�darki
readfile($filenameZip);

//usuwamy przegrane pliki - �eby nie za�mieca�y serwera
foreach($uploadedFilesAll as $file) {
	
	$filePath = $file['user_name_path'];
	$fileTempName = $file['temp_name'];
	$fileTempPath = $converted . '/' . $fileTempName;
	
	if (file_exists($filePath)) {
		//funkcja unlink usuwa plik
		unlink($filePath);
	}
	
	if (file_exists($fileTempPath)) {
		unlink($fileTempPath);
	}
	
}

if (file_exists($filenameZip)) {
	unlink($filenameZip);
}


die;

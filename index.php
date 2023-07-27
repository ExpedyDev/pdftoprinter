<!DOCTYPE html>
<html>
<head>
	<title>Créer un PDF et imprimer</title>
</head>
<body>
	<h1>Créer un PDF et imprimer</h1>
	<form method="post" action="" enctype="multipart/form-data">
		<label for="text">Saisissez le texte à imprimer en PDF :</label><br>
		<input type="text" id="text" name="text"><br><br>
		<label for="files">Envoyer des fichiers PDF :</label><br>
		<input type="file" id="files" name="files[]" multiple><br><br>
		<button type="submit" name="submit">Imprimer</button>
	</form>
	<?php
	// Si le formulaire est soumis
	if (isset($_POST['submit'])) {
		// Vérifier si le champ texte est rempli
		if (!empty($_POST['text']) || !empty($_FILES['files']['tmp_name'][0])) {
			// Récupérer le texte saisi par l'utilisateur
			$text = $_POST['text'];

			// Générer le nom du fichier PDF avec la date actuelle
			$pdfName = "pdf_" . date('Ymd_His') . ".pdf";

			// Inclure la bibliothèque FPDF et FPDI
			require('fpdf/fpdf.php');
			require('fpdi/src/autoload.php');

			// Créer un objet FPDI (PDF étendu) avec FPDF
			$pdf = new \setasign\Fpdi\Fpdi('P', 'mm', array(105, 148));

			// Gérer les fichiers PDF téléversés
			if (!empty($_FILES['files']['tmp_name'][0])) {
				foreach ($_FILES['files']['tmp_name'] as $file) {
					if (!empty($file) && file_exists($file)) {
						// Ajouter les pages du fichier PDF au PDF en cours
						$pageCount = $pdf->setSourceFile($file);
						for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
							$tplIdx = $pdf->importPage($pageNumber);
							$pdf->AddPage();
							$pdf->useTemplate($tplIdx);

						}
					}
				}
			}

			// Ajouter une page au PDF si le champ texte est rempli
			if (!empty($text)) {
				$pdf->AddPage();
				$pdf->SetFont('Arial','B',16);
				$pdf->Cell(40, 10, $text);
			}

			// Générer le fichier PDF
			$pdf->Output('F', $pdfName);

			// Envoyer la requête à l'API Expedy pour imprimer le PDF
			$file_url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $pdfName;
			sendToExpedyPrinter($file_url);
			echo "<p>Vos fichiers ont été envoyés à l'imprimante !</p>";
		} else {
			echo "<p>Aucun texte ou fichier PDF spécifié.</p>";
		}
	}

	function sendToExpedyPrinter($file_url) {
		// Printer UID API Expedy.com
		$printer_uid = 'SAISIR UID PRINTER';

		// Message à imprimer
		$data = array(
			'usb_msg' => $file_url,
			'origin' => 'Your defined origin tag.. a uri, a name ..'
		);
		// Infos API
		$options = array(
			CURLOPT_URL => "https://www.expedy.fr/api/v2/devices/" . $printer_uid . "/usb/4/print",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
			// Vos identifiants API token:secretkey
				"Authorization: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
				"Content-Type: application/json"
			),
		);

		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			echo $response;
		}


	}
	?>
</body>
</html>

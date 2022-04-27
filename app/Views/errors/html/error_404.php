<!DOCTYPE html>
<html lang="en">

<head>
	<base href="<?= base_url() ?>">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<meta name="description" content="Application">
	<meta name="author" content="Wahyu Kristiawan">
	<meta name="keyword" content="Application">
	<title>404 Page Not Found</title>
	<link rel="icon" type="image/png" href="aicons/logo.png">
	<link rel="manifest" href="assets/favicon/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="assets/favicon/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
	<!-- Vendors styles-->
	<link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
	<link rel="stylesheet" href="css/vendors/simplebar.css">
	<!-- Main styles for this application-->
	<link href="css/style.css" rel="stylesheet">
	<!-- We use those styles to show code examples, you should remove them in your application.-->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.23.0/themes/prism.css">
	<link href="css/examples.css" rel="stylesheet">
	<!-- Global site tag (gtag.js) - Google Analytics-->
</head>

<body>
	<div class="bg-light min-vh-100 d-flex flex-row align-items-center">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-6">
					<div class="clearfix text-dark">
						<h1 class="float-start display-3 me-4">404</h1>
						<h4 class="pt-3">Oops! You're lost.</h4>
						<p class="text-medium-emphasis">
							<?php if (!empty($message) && $message !== '(null)') : ?>
								<?= esc($message) ?>
							<?php else : ?>
								The page you are looking for was not found.
							<?php endif ?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- CoreUI and necessary plugins-->
	<script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
	<script src="vendors/simplebar/js/simplebar.min.js"></script>
	<!-- We use those scripts to show code examples, you should remove them in your application.-->
	<script src="https://cdn.jsdelivr.net/npm/prismjs@1.24.1/prism.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/prismjs@1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/prismjs@1.24.1/plugins/unescaped-markup/prism-unescaped-markup.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/prismjs@1.24.1/plugins/normalize-whitespace/prism-normalize-whitespace.js"></script>
	<script>
	</script>

</body>

</html>
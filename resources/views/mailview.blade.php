<!DOCTYPE html>
<html>
<head>
	<title>Mail Viewer</title>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Olive CMS | Admin</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- keeping jquey min ahead of all other scripts and js plugins to work -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.min.css')}}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <!-- Bootstrap 3.3.6 -->
	<script src="{{asset('bootstrap/js/bootstrap.min.js')}}"></script>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body>
	<table>
		<thead>
			<th>ID.</th>
			<th>Subject</th>
			<th>Date</th>
		</thead>
		@if($emailDatas)
			@foreach ($emailDatas as $emailData)
				<tr>
					<td> <?php echo $emailData['id'] ?> </td>
					<td> <?php echo $emailData['subject'] ?></td>
					<td> <?php echo $emailData['date'] ?></td>
				</tr>
		    	
			@endforeach
		@else
			<tr>
				<td>There are no emails!</td>
			</tr>
		@endif	
	</table>
</body>
</html>
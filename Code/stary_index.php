<!doctype html>
<html lang="pl">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Search</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="simple-notify.min.css" rel="stylesheet">	
	</head>
	<body>
		<div class="container mt-5">
			<div class="row justify-content-center mt-5">
				<div class="col-3">
					<form method="POST" id="form_number">
						<div class="mb-3">
							<label for="number" class="form-label">Numer zamówienia</label>
							<input type="text" class="form-control rounded-0" id="number" name="number">
						</div>
						<div class="mb-3 input-group">
							<button type="submit" name="action" value="send" class="btn btn-success rounded-0 w-50">Szukaj</button>
							<button type="button" class="btn btn-warning rounded-0 w-50">Wyczyść</button>
						</div>
					</form>
				</div>
			</div>
			<div class="row justify-content-center mt-5">
				<div class="col-md-10">
					<table class="table">
						<thead>
							<tr>
								<td>Lokalizacja</td>
								<td>Nazwa</td>
								<td>Ilość</td>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
		<script src="simple-notify.min.js"></script>
		<script>
			$(document).ready(function(){
				$('.btn-warning').on("click", function(e){
					e.preventDefault();
					$('.table tbody').html("");
				});
				
				$('#form_number').on("submit", function(e){
					$('.btn-success').prop('disabled', true);
					e.preventDefault();
					$.ajax({
						url: "engine.php",
						data: $(this).serializeArray(),
						type: "POST"					
					}).always(function(data,status,xhr){		
						$('#number').val('');
						$('.btn-success').prop('disabled', false);
						try
						{
							data = JSON.parse(data);
							if (typeof data.msg != 'undefined')
							{
								new Notify ({text: data.msg, effect: 'fade', status: 'info', autoclose: true, autotimeout: 3000, type: 1})
							}
							else if (data.length)
							{
								$.each(data, function(i, obj){
									$('.table tbody').append('<tr data-id="'+obj.id+'" ><td>'+obj.location+'</td><td>'+obj.name+'</td><td>'+obj.count+' Szt.</td></tr>');
								});
							}								
						}					
						catch (error) 
						{
							alert("Napotkano błąd");
							console.log(data);
							console.error(error);
						}											
					});
				});
			});
		
			if (window.history.replaceState)
			{
				window.history.replaceState(null, null, window.location.href); 
			}	
		</script>
	</body>
</html>
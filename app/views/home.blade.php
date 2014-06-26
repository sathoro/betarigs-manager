@extends('layout')

@section('content')
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title">Pool Settings</h3>
					</div>
					<div class="panel-body">
						<p>Note: Changing your pool settings will automatically update any existing rentals and apply to future rentals as well</p>
						{{ Form::open(array('id' => 'pool-settings', 'url' => 'pool-settings', 'class' => '')) }}
							<div class="form-group">
								<label for="pool_url">Pool URL</label>
								<div class="input-group">
									<span class="input-group-addon">stratum+tcp://</span>
									<input type="text" name="pool_url" id="pool_url" class="form-control" value="{{ Cache::get('pool_url') }}" placeholder="pool.example.com:3333" />
								</div>
							</div>
							<div class="form-group">
								<label for="pool_username">Pool worker name</label>
								<input type="text" name="pool_username" id="pool_username" class="form-control" value="{{ Cache::get('pool_username') }}" placeholder="worker_example.1" />
							</div>
							<div class="form-group">
								<label for="pool_password">Pool worker password</label>
								<input type="text" name="pool_password" id="pool_password" class="form-control" value="{{ Cache::get('pool_password') }}" placeholder="x" />
							</div>
							<button type="submit" class="btn btn-default">Save</button>
						{{ Form::close() }}
					</div>
				</div>

				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title">Your Rentals</h3>
					</div>
					<div class="panel-body">
						<div id="current-rentals">
							Loading...
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title">Rental Form</h3>
					</div>
					<div class="panel-body">
						{{ Form::open(array('id' => 'submit-rentals', 'url' => 'submit-rentals', 'class' => '')) }}
							<div class="form-group">
								<label for="algorithm">Algorithm</label>
								<select name="algorithm" id="algorithm" class="form-control">
									@foreach ($algorithms as $algorithm)
										<option value="{{ $algorithm['id'] }}">{{ $algorithm['name'] }}</option>
									@endforeach
								</select>
							</div>
							<div class="form-group">
								<label for="hashing_power">Hashing power to rent</label>
								<div class="input-group">
									<input type="text" name="hashing_power" id="hashing_power" class="form-control" />
									<span class="input-group-addon"> Mh/s</span>
								</div>
							</div>
							<div class="form-group">
								<label for="max_price">Maximum price per rig</label>
								<div class="input-group">
									<input type="text" name="max_price" id="max_price" class="form-control" />
									<span class="input-group-addon"> BTC/Mh/day</span>
								</div>
							</div>
							<div class="form-group">
								<label for="rental_duration">Rental duration</label>
								<select name="rental_duration" id="rental_duration" class="form-control">
									<option value="3">3 hours</option>
									<option value="6">6 hours</option>
									<option value="12">12 hours</option>
									<option value="24">1 day</option>
									<option value="48">2 days</option>
									<option value="72">3 days</option>
									<option value="168">1 week</option>
								</select>
							</div>

							<button type="submit" class="btn btn-default">Find Rigs</button>
						{{ Form::close() }}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title">Rental Options</h3>
					</div>
					<div class="panel-body">
						<div id="rental-options">
							Submit the form on the left to view rental options
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@stop

@section('scripts')
	<script>
      $(function() {
        function prettify_rigs(rigs, hrs) {
          if (rigs.length === 0) {
            return "No rigs matching your criteria found";
          }

          var total_hashing_power = 0,
            btc_due = 0,
            html = '<table class="table">',
            rig_ids = [];
          html += '<thead><tr>';
          html += '<td>#</td><td>Rig</td><td>Speed (Mh/s)</td><td>Price (BTC/Mh/day)</td>';
          html += '</thead></tr><tbody>';
          $.each(rigs, function(i, e) {
            html += '<tr><td>' + (i + 1) + '</td><td><a target="_blank" href="https://www.betarigs.com/rig/' + e['id'] + '">'  + e['name'] + '</a></td><td>' + e['speed_mhs'] + '</td><td>' + e['price_mhd'] + '</td></tr>'; 
            btc_due += (hrs / 24) * e['price_day'];
            total_hashing_power += e['speed_mhs'];
            rig_ids.push(e['id']);
          });
          html += '</tbody></table>';
          html += '<div>' + (btc_due * 1.03).toFixed(8) + ' BTC (after 3% Betarigs fee)</div>';
          html += '<div style="margin-bottom:8px">' + total_hashing_power + ' Mh/s</div>';
          html += '<button type="button" id="rent-now" class="btn btn-default" data-rigs="' + rig_ids.join(',') + '" data-hrs="' + hrs + '">Rent Now</button>';
          html += '<div style="margin-top:8px" id="rental-output"></div>';
          return html;
        }

        function prettify_rentals(rentals) {
          if (rentals.length === 0) {
            return "No rentals found";
          }

          var html = '<table class="table">';
          html += '<thead><tr>';
          html += '<td>#</td><td>Rig</td><td>Speed (Mh/s)</td><td>Total Price</td><td>Duration</td><td>Status</td>';
          html += '</thead></tr><tbody>';
          $.each(rentals, function(i, e) {
            html += '<tr><td>' + (i + 1) + '</td><td><a target="_blank" href="https://www.betarigs.com/rig/' + e['rig']['id'] + '">'  + e['rig']['id'] + '</a></td><td>' + (e['rig']['speed'] / 1000) + '</td><td>' + e['payment']['bitcoin']['price']['value'] + '</td><td>' + e['duration']['current_duration']['value'] + ' ' + e['duration']['current_duration']['unit'] + '</td><td>' + format_rental_status(e['status']) + '</td></tr>';
          });
          html += '</tbody></table>';
          return html;
        }

        function format_rental_status(status) {
          var errors = ['payment_not_received'],
            warnings = ['waiting_payment', 'waiting_payment_confirmation'],
            html = $("<span />").attr('style', 'text-transform:capitalize').addClass('label').text(status.replace(/_/g, ' '));

          if ($.inArray(status, errors) > -1) html.addClass('label-danger');
          else if ($.inArray(status, warnings) > -1) html.addClass('label-warning');
          else html.addClass('label-success');

          return html[0].outerHTML;
        }

        $("#current-rentals").html(prettify_rentals({{ json_encode($rentals) }}));

        $('form#submit-rentals').submit(function() {
          var $form = $(this);
          $('#rental-options').text('Loading...');
          $.post($form.attr('action'), $form.serialize(), function(data) {
            $('#rental-options').html(prettify_rigs($.parseJSON(data), parseInt($form.find('#rental_duration').val())));
          });
          return false;
        });

        $('form#pool-settings').submit(function() {
          var $form = $(this);
          $.post($form.attr('action'), $form.serialize(), function(data) {
            window.location.reload(true);
          });
          return false;
        });

        $(document).on('click', 'button#rent-now', function() {
          var rigs = $(this).attr('data-rigs'),
            hrs = $(this).attr('data-hrs');

          $(this).attr('disabled', true);

          $('#rental-output').text('Loading...');
            
          $.post('/do-rent', {'rigs': rigs, 'hrs': hrs}, function(data) {
          	$('#rental-output').text('');

            if (data['errors'].length === 0) {
            	window.location.reload(true);
            }
            else {
            	$('#rental-output').html(data['errors'].length + ' rig' + (data['errors'].length > 1 ? 's' : '') + ' not rented due to error' + (data['errors'].length > 1 ? 's' : '') + '  below.<ul style="padding-left:18px"></ul>')
            	$.each(data['errors'], function(i, e) {
            		$('#rental-output').find('ul').append("<li>" + e + "</li>");
            	});
            }
          });
        });
      });
    </script>
@stop
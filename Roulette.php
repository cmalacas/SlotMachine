<?php
/* Template Name: Spin A Wheel */
?>
<?php 

global $wpdb;

$postId = $_GET['giveaway-id'];

$title = get_the_title( $postId );

$userSubs = all_user_roles();

$oneOffResults = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}one_of_package WHERE post_id = $postId");

$DateTimeCountDown = strtotime(get_field('datetime_countdown', $postId));

$now = strtotime(date("Y-m-d"));

$total = 0;

$oneOffUsers = [];

foreach($oneOffResults as $row) {
    $oneOffUsers[] = $row->user_id;
}

$users = array_unique(array_merge($userSubs, $oneOffUsers));

$tables = [];

if (!empty($users)) {

    $arg_user = $users; 

    $numberindex = 1;

    foreach($arg_user as $key => $user) {

        $user_info = get_userdata($user);

        if ($user_info) {

            $first_name = $user_info->first_name;
            $last_name = $user_info->last_name;

            $id_price = get_user_meta($user, 'id_price', true);
            $subscription_id = get_user_meta($user, 'id_subscription', true);
            $customer_id     = get_user_meta($user, 'customer_id_stripe', true);
            $status_sub = get_user_meta($user, 'subscription_status', true);

            $userEntry = get_user_meta($user, 'number_entries', true);

            $overRideResult = $wpdb->get_row("SELECT override FROM {$wpdb->prefix}entry_override WHERE user_id = $user AND post_id = $postId");                        

            $current_role = $user_info->roles[0];

            if ($current_role == 'entry-member') {
                $role = 'MOD 1';
            } elseif ($current_role == 'premium-member') {
                $role = 'MOD 2';
            } elseif ($current_role == 'elite-member') {
                $role = 'MOD 3';
            } else {
                $role = 'One Time';
            }

            $entry = 0;

            if ($status_sub === 'active') {

                $entry = (int)$userEntry;

            } else {

                $stripeQuery = "SELECT * FROM {$wpdb->prefix}stripe_transactions WHERE user_id = {$user} AND status = 'succeeded' ORDER BY created DESC";

                $stripeResult = $wpdb->get_row($stripeQuery);

                if (!empty($stripeResult)) {

                    $lastUpdate = date("Y-m-d", $stripeResult->created);
                    $oneMonth = date("Y-m-d", strtotime("+1 Month", $stripeResult->created));

                    //echo $oneMonth;

                    if ($oneMonth > date("Y-m-d")) {

                        $entry = (int)$userEntry;

                    }

                }

            }

            if (!empty($overRideResult)) {
                $entry += $overRideResult->override;
            }            

            $query = "SELECT SUM(IF(name_package = 'PLATINUM', 50, 
                                IF(name_package = 'GOLD', 20, 
                                IF(name_package = 'SILVER', 5, 
                                IF(name_package = 'BRONZE', 1, 0
                                ))))) as entry 
                        FROM {$wpdb->prefix}one_of_package o
                        JOIN {$wpdb->prefix}postmeta p ON p.post_id = o.post_id AND meta_key = 'datetime_countdown'
                        WHERE user_id = $user AND 
                                o.post_id = $postId";

            $results =  $wpdb->get_row($query); 

            $entry += ($results->entry ? $results->entry : 0);

            if ($entry > 0) {

                $color = sprintf('#%06X', mt_rand(0, 0x7FFFFF) & ~0x808080);

                $tables[] = [
                                'name' => sprintf("%s %s", $first_name, $last_name),
                                'entry' => $entry,
                                'color' => $color
                    ];
            }
            

        }

    }

}


?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width" />
    <!-- Required library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <!-- Bootstrap theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <title><?= $title ?></title>
  </head>
  <body>
    <div class="container">
      <h4 align="center"><?= $title ?></h4>
      <div class="row">
        <div class="col-xs-12" align="center">
          <div id="wheel">
            <canvas id="canvas" width="260" height="260"></canvas>
          </div>
        </div>
      </div>

      
      <!--  end row -->
      <div class="row">
        <div class="col-xs-6" align="center">
          <button type="button" class="btn btn-success" onclick="spin()">Spin Now!</button>
        </div>
        <div class="col-xs-6" align="center">
          <button type="button" id="stop" class="btn btn-info" onclick="stops()">Stop Now!</button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-12 mr-4 ml-4">
        <table class="table">
          <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Name</th>
            </tr>
          </thead>
          <tbody>
            <?php $counter = 0; foreach($tables as $row) { ?>

              <?php for( $i=1; $i<= $row['entry']; $i++) { ?>

                <tr>

                  <td><?= ++$counter; ?></td>
                  <td><?= $row['name'] ?></td>

                </tr>
              
              <?php } ?>

            <?php } ?>
          </tbody>
        </div>
      </div>
    </div>
    <!-- end container -->
    <br>    
  </body>
  <script language="JavaScript">

    var label_data = [
            <?php 

                $names = [];
                $colors = [];
            
                foreach($tables as $row) { 

                   
                
                    for( $i=1; $i<= $row['entry']; $i++) {

                        $names[] = sprintf('"%s"', $row['name']);
                        $colors[] = sprintf("'%s'", $row['color']);

                    }
                }

            ?>

            <?= implode( ',', $names ) ?>

        ];

    var color_data = ['#fedf30', '#fdb441', '#fd6930', '#eb5454', '#bf9dd3', '#29b8cd', "#00f2a6", "#f67"];

  function create_spinner() {
    
    var color = color_data;
    var label = label_data;
    var slices = color.length;
    var sliceDeg = 360 / slices;
    var deg = rand(0, 360);
    var speed = 10;
    var slowDownRand = 0;
    var ctx = canvas.getContext('2d');
    var width = canvas.width; // size
    var center = width / 2; // center
    ctx.clearRect(0, 0, width, width);
    for (var i = 0; i < slices; i++) {
      ctx.beginPath();
      ctx.fillStyle = color[i];
      ctx.moveTo(center, center);
      ctx.arc(center, center, width / 2, deg2rad(deg), deg2rad(deg + sliceDeg));
      ctx.lineTo(center, center);
      ctx.fill();
      var drawText_deg = deg + sliceDeg / 2;
      ctx.save();
      ctx.translate(center, center);
      ctx.rotate(deg2rad(drawText_deg));
      ctx.textAlign = "right";
      ctx.fillStyle = "#fff";
      ctx.font = 'bold 15px sans-serif';
      // ctx.fillText(label[i], 400, 5);
      ctx.restore();
      deg += sliceDeg;
    }
  }
  create_spinner();
  var deg = rand(0, 360);
  var speed = 10;
  var ctx = canvas.getContext('2d');
  var width = canvas.width; // size
  var center = width / 2; // center
  var isStopped = false;
  var lock = false;
  var slowDownRand = 0;

  function spin() {
    
    
    var color = color_data;
    var label = label_data;
    var slices = label.length;
    var sliceDeg = 360 / color_data.length;
    deg += speed;
    deg %= 360;
    // Increment speed
    if (!isStopped && speed < 3) {
      speed = speed + 1 * 0.1;
    }
    // Decrement Speed
    if (isStopped) {
      if (!lock) {
        lock = true;
        slowDownRand = rand(0.994, 0.998);
      }
      speed = speed > 0.2 ? speed *= slowDownRand : 0;
    }
    // Stopped!
    if (lock && !speed) {
      var ai = Math.floor(((360 - deg - 90) % 360) / (360 / label.length)); // deg 2 Array Index
      ai = (slices + ai) % slices; // Fix negative index
      //return alert("You got:\n"+ label[ai] ); // Get Array Item from end Degree
      return swal({
        title: "Wow!!!!",
        text: "It's #" + ai + " " + label[ai] + " won",
        type: "success",
        confirmButtonText: "OK",
        closeOnConfirm: false
      });
    }
    ctx.clearRect(0, 0, width, width);
    for (var i = 0; i < color_data.length; i++) {
      ctx.beginPath();
      ctx.fillStyle = color[i];
      ctx.moveTo(center, center);
      ctx.arc(center, center, width / 2, deg2rad(deg), deg2rad(deg + sliceDeg));
      ctx.lineTo(center, center);
      ctx.fill();
      var drawText_deg = deg + sliceDeg / 2;
      ctx.save();
      ctx.translate(center, center);
      ctx.rotate(deg2rad(drawText_deg));
      ctx.textAlign = "right";
      ctx.fillStyle = "#fff";
      ctx.font = 'bold 15px sans-serif';
      // ctx.fillText(label[i], 100, 5);
      ctx.restore();
      deg += sliceDeg;
    }
    window.requestAnimationFrame(spin);
  }

  function stops() {
    isStopped = true;
  }

  function deg2rad(deg) {
    return deg * Math.PI / 180;
  }

  function rand(min, max) {
    return Math.random() * (max - min) + min;
  }
</script>
</html>
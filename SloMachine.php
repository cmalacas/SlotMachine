<?php
/* Template Name: Slot Machine */

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

$total_entries = 0;

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
                        WHERE user_id = $user AND post_id = $postId";

            $results =  $wpdb->get_row($query); 

            $entry += ($results->entry ? $results->entry : 0);

            if ($entry > 0) {

                $color = sprintf('#%06X', mt_rand(0, 0x7FFFFF) & ~0x808080);

                $total_entries += $entry;

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Karla:wght@800&display=swap" rel="stylesheet">
<style>
  body {
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  font-family: 'Karla', sans-serif;
}

#app {
  width: 100%;
  height: 100%;
  background: #fcd801;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.doors {
  display: flex;
  background: #fff;
  padding: 20px 40px;
  border-radius: 20px;
}

.door-wrapper {
  display: flex;
  padding: 10px;
  background: #f5f5f5;
  border-radius: 15px;
}

.door-wrapper > .door:first-child {
  margin: 0 10px 0 10px;
}

.door {
  background: #fafafa;
  width: 70px;
  height: 140px;
  overflow: hidden;
  border-radius: 5px;
  margin: 0 10px 0 0;
  box-shadow: 0px 3px 15px #ccc;
}

.boxes {
  transition: transform 1s ease-in-out;
}

.box {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 6rem;  
}

.buttons {
  margin: 1rem 0 2rem 0;
}

button {
  cursor: pointer;
  font-size: 1.2rem;
  margin: 0 0.2rem;
  border: none;
  border-radius: 5px;
  padding: 10px 15px;
}

.info {
  position: fixed;
  bottom: 0;
  width: 100%;
  text-align: center;
}
</style>
<div id="app">
  <h1 style="text-align:center; margin: 15px 0 40px"><?= $title ?></h1>
  <div class="doors">
    <div class="door-wrapper">
      <div class="door">
        <div class="boxes">
          <!-- <div class="box">?</div> -->
        </div>
      </div>

      <div class="door">
        <div class="boxes">
          <!-- <div class="box">?</div> -->
        </div>
      </div>

      <div class="door">
        <div class="boxes">
          <!-- <div class="box">?</div> -->
        </div>
      </div>

      <div class="door">
        <div class="boxes">
          <!-- <div class="box">?</div> -->
        </div>
      </div>

    </div>
  </div>

  <div class="buttons">
    <button id="spinner">Play</button>
    <button id="reseter">Reset</button>
  </div>

  <div style="display: none" class="winner">
    <h1 style="text-align:center">Winner</h1>
    <div id="winner-name">
      
    </div>
  </div>

  <div style="display: none" class="row">
      <div class="col-xs-12 mr-4 ml-4">
        <table class="table">
          <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Name</th>            
             
            </tr>
          </thead>
          <tbody>
            <?php 
            
              $counter = 0; 
              
              foreach($tables as $row) { 
              
                $entries = 9999 * $row['entry'] / $total_entries;

                

                for( $i=1; $i <= $entries; $i++) {

                  $_counter = ++$counter;
              
            ?>

              <tr id="entry<?= $_counter ?>">

                <td class="counter"><?= $_counter ?></td>

                <td class="name"><?= $row['name'] ?></td>

                <td><?= $row['entry'] ?></td>

              </tr>

              <?php } ?>

            <?php } 

                $totalUser = count($tables);
            
                while ( $counter < 9999 ) {

                  $index = rand(1, $totalUser);

                  $row = $tables[$index];

                  $_counter = ++$counter;

                  $tables[$index] = [
                              'name' => $row['name'],
                              'entry' => $row['entry']
                            ];

                ?>

                  <tr id="entry<?= $_counter ?>">
                    <td><?= $_counter ?></td>
                    <td class="name"><?= $row['name'] ?></td>
                    <td><?= $row['entry'] ?></td>
                  </tr>

                <?php

                }
            
            ?>
          </tbody>
        </table>
      </div>
    </div>
</div>

<script>
  (function () {
  const items = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0', 
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '0',        
  ];
  const doors = document.querySelectorAll('.door');

  const winner = document.querySelectorAll('.winner');

  let digit = [];
  let winningNumber = 0;

  document.querySelector('#spinner').addEventListener('click', spin);

  document.querySelector('#reseter').addEventListener('click', init);

  function init(firstInit = true, groups = 1, duration = 10) {
    for (const door of doors) {
      if (firstInit) {
        digit = [];
        door.dataset.spinned = '0';
        document.getElementById('winner-name').innerHTML = '';
        winner[0].style.display = 'none';
      } else if (door.dataset.spinned === '1') {
        return;
      }

      const boxes = door.querySelector('.boxes');
      const boxesClone = boxes.cloneNode(false);
      const pool = ['❓'];

      if (!firstInit) {
        const arr = [];
        for (let n = 0; n < (groups > 0 ? groups : 1); n++) {
          arr.push(...items);
        }

        pool.push(...shuffle(arr));

        boxesClone.addEventListener(
          'transitionstart',
          function () {
            door.dataset.spinned = '1';
            this.querySelectorAll('.box').forEach((box) => {
              box.style.filter = 'blur(1px)';
            });
          },
          { once: true }
        );

        boxesClone.addEventListener(
          'transitionend',
          function () {
            this.querySelectorAll('.box').forEach((box, index) => {
              box.style.filter = 'blur(0)';
              if (index > 0) this.removeChild(box);
            });
          },
          { once: true }
        );
      }

      // console.log('pool', pool[pool.length - 1])

      if (parseInt(pool[pool.length - 1]) >= 0) {

        digit.push( parseInt(pool[pool.length - 1]) );

      }

      for (let i = pool.length - 1; i >= 0; i--) {
        const box = document.createElement('div');
        box.classList.add('box');
        box.style.width = door.clientWidth + 'px';
        box.style.height = door.clientHeight + 'px';
        box.textContent = pool[i];
        boxesClone.appendChild(box);
      }
      boxesClone.style.transitionDuration = `${duration > 0 ? duration : 1}s`;
      boxesClone.style.transform = `translateY(-${door.clientHeight * (pool.length - 1)}px)`;
      door.replaceChild(boxesClone, boxes);
    }
  }

  async function spin() {
    
    init(false, 1, 1);    

    for (const door of doors) {
      const boxes = door.querySelector('.boxes');
      const duration = parseInt(boxes.style.transitionDuration);
      boxes.style.transform = 'translateY(0)';
      await new Promise((resolve) => setTimeout(resolve, duration * 100));
    }

    // console.log('digit', digit);

    winningNumber = (digit[0] * 1000) + (digit[1] * 100) + (digit[2] * 10) + digit[3];

    winningRow = document.getElementById('entry' + winningNumber);

    // console.log('winningRow', winningRow, winningRow.getElementsByClassName('name'));

    document.getElementById('winner-name').innerHTML = '<h1 style="text-align:center">' + winningRow.getElementsByClassName('name')[0].innerHTML + '</h1>';

    setTimeout(() => {

      winner[0].style.display = 'block';

    }, 2000)

    
    
  }

  function shuffle([...arr]) {   
    
      let m = arr.length;
      while (m) {
        const i = Math.floor(Math.random() * m--);
        [arr[m], arr[i]] = [arr[i], arr[m]];
      }   

      return arr;    

  }

  init(true, 1, 1);
})();
</script>

<script>

  var retina = window.devicePixelRatio,

  // Math shorthands
  PI = Math.PI,
  sqrt = Math.sqrt,
  round = Math.round,
  random = Math.random,
  cos = Math.cos,
  sin = Math.sin,

  // Local WindowAnimationTiming interface
  rAF = window.requestAnimationFrame,
  cAF = window.cancelAnimationFrame || window.cancelRequestAnimationFrame,
  _now = Date.now || function () {return new Date().getTime();};

  // Local WindowAnimationTiming interface polyfill
  (function (w) {
    /**
    * Fallback implementation.
    */
    var prev = _now();

    function fallback(fn) {
      var curr = _now();
      var ms = Math.max(0, 16 - (curr - prev));
      var req = setTimeout(fn, ms);
      prev = curr;
      return req;
    }

    /**
    * Cancel.
    */

    var cancel = w.cancelAnimationFrame 
    || w.webkitCancelAnimationFrame
    || w.clearTimeout;

    rAF = w.requestAnimationFrame
    || w.webkitRequestAnimationFrame
    || fallback;

    cAF = function(id){
      cancel.call(w, id);
      };
  }(window));

  document.addEventListener("DOMContentLoaded", function() {
    var speed = 50,
    
    duration = (1.0 / speed),
    confettiRibbonCount = 11,
    ribbonPaperCount = 30,
    ribbonPaperDist = 8.0,
    ribbonPaperThick = 8.0,
    confettiPaperCount = 95,

    DEG_TO_RAD = PI / 180,
    RAD_TO_DEG = 180 / PI,

    colors = [
      ["#df0049", "#660671"],
      ["#00e857", "#005291"],
      ["#2bebbc", "#05798a"],
      ["#ffd200", "#b06c00"]
    ];

    function Vector2(_x, _y) {

      this.x = _x, this.y = _y;
      this.Length = function() {
        return sqrt(this.SqrLength());
      }

      this.SqrLength = function() {
        return this.x * this.x + this.y * this.y;
      }
      this.Add = function(_vec) {
        this.x += _vec.x;
        this.y += _vec.y;
      }
      this.Sub = function(_vec) {
        this.x -= _vec.x;
        this.y -= _vec.y;
      }
      this.Div = function(_f) {
        this.x /= _f;
        this.y /= _f;
      }

      this.Mul = function(_f) {
        this.x *= _f;
        this.y *= _f;
      }

      this.Normalize = function() {
        var sqrLen = this.SqrLength();
        if (sqrLen != 0) {
          var factor = 1.0 / sqrt(sqrLen);
          this.x *= factor;
          this.y *= factor;
        }
      }

      this.Normalized = function() {
        var sqrLen = this.SqrLength();
        if (sqrLen != 0) {
          var factor = 1.0 / sqrt(sqrLen);
          return new Vector2(this.x * factor, this.y * factor);
        }
        return new Vector2(0, 0);
      }
    }
    
Vector2.Lerp = function(_vec0, _vec1, _t) {
return new Vector2((_vec1.x - _vec0.x) * _t + _vec0.x, (_vec1.y - _vec0.y) * _t + _vec0.y);
}
Vector2.Distance = function(_vec0, _vec1) {
return sqrt(Vector2.SqrDistance(_vec0, _vec1));
}
Vector2.SqrDistance = function(_vec0, _vec1) {
var x = _vec0.x - _vec1.x;
var y = _vec0.y - _vec1.y;
return (x * x + y * y + z * z);
}
Vector2.Scale = function(_vec0, _vec1) {
return new Vector2(_vec0.x * _vec1.x, _vec0.y * _vec1.y);
}
Vector2.Min = function(_vec0, _vec1) {
return new Vector2(Math.min(_vec0.x, _vec1.x), Math.min(_vec0.y, _vec1.y));
}
Vector2.Max = function(_vec0, _vec1) {
return new Vector2(Math.max(_vec0.x, _vec1.x), Math.max(_vec0.y, _vec1.y));
}
Vector2.ClampMagnitude = function(_vec0, _len) {
var vecNorm = _vec0.Normalized;
return new Vector2(vecNorm.x * _len, vecNorm.y * _len);
}
Vector2.Sub = function(_vec0, _vec1) {
return new Vector2(_vec0.x - _vec1.x, _vec0.y - _vec1.y, _vec0.z - _vec1.z);
}

function EulerMass(_x, _y, _mass, _drag) {
this.position = new Vector2(_x, _y);
this.mass = _mass;
this.drag = _drag;
this.force = new Vector2(0, 0);
this.velocity = new Vector2(0, 0);
this.AddForce = function(_f) {
  this.force.Add(_f);
}
this.Integrate = function(_dt) {
  var acc = this.CurrentForce(this.position);
  acc.Div(this.mass);
  var posDelta = new Vector2(this.velocity.x, this.velocity.y);
  posDelta.Mul(_dt);
  this.position.Add(posDelta);
  acc.Mul(_dt);
  this.velocity.Add(acc);
  this.force = new Vector2(0, 0);
}
this.CurrentForce = function(_pos, _vel) {
  var totalForce = new Vector2(this.force.x, this.force.y);
  var speed = this.velocity.Length();
  var dragVel = new Vector2(this.velocity.x, this.velocity.y);
  dragVel.Mul(this.drag * this.mass * speed);
  totalForce.Sub(dragVel);
  return totalForce;
}
}

function ConfettiPaper(_x, _y) {
this.pos = new Vector2(_x, _y);
this.rotationSpeed = (random() * 600 + 800);
this.angle = DEG_TO_RAD * random() * 360;
this.rotation = DEG_TO_RAD * random() * 360;
this.cosA = 1.0;
this.size = 5.0;
this.oscillationSpeed = (random() * 1.5 + 0.5);
this.xSpeed = 40.0;
this.ySpeed = (random() * 60 + 50.0);
this.corners = new Array();
this.time = random();
var ci = round(random() * (colors.length - 1));
this.frontColor = colors[ci][0];
this.backColor = colors[ci][1];
for (var i = 0; i < 4; i++) {
  var dx = cos(this.angle + DEG_TO_RAD * (i * 90 + 45));
  var dy = sin(this.angle + DEG_TO_RAD * (i * 90 + 45));
  this.corners[i] = new Vector2(dx, dy);
}
this.Update = function(_dt) {
  this.time += _dt;
  this.rotation += this.rotationSpeed * _dt;
  this.cosA = cos(DEG_TO_RAD * this.rotation);
  this.pos.x += cos(this.time * this.oscillationSpeed) * this.xSpeed * _dt
  this.pos.y += this.ySpeed * _dt;
  if (this.pos.y > ConfettiPaper.bounds.y) {
    this.pos.x = random() * ConfettiPaper.bounds.x;
    this.pos.y = 0;
  }
}
this.Draw = function(_g) {
  if (this.cosA > 0) {
    _g.fillStyle = this.frontColor;
  } else {
    _g.fillStyle = this.backColor;
  }
  _g.beginPath();
  _g.moveTo((this.pos.x + this.corners[0].x * this.size) * retina, (this.pos.y + this.corners[0].y * this.size * this.cosA) * retina);
  for (var i = 1; i < 4; i++) {
    _g.lineTo((this.pos.x + this.corners[i].x * this.size) * retina, (this.pos.y + this.corners[i].y * this.size * this.cosA) * retina);
  }
  _g.closePath();
  _g.fill();
}
}
ConfettiPaper.bounds = new Vector2(0, 0);

function ConfettiRibbon(_x, _y, _count, _dist, _thickness, _angle, _mass, _drag) {
this.particleDist = _dist;
this.particleCount = _count;
this.particleMass = _mass;
this.particleDrag = _drag;
this.particles = new Array();
var ci = round(random() * (colors.length - 1));
this.frontColor = colors[ci][0];
this.backColor = colors[ci][1];
this.xOff = (cos(DEG_TO_RAD * _angle) * _thickness);
this.yOff = (sin(DEG_TO_RAD * _angle) * _thickness);
this.position = new Vector2(_x, _y);
this.prevPosition = new Vector2(_x, _y);
this.velocityInherit = (random() * 2 + 4);
this.time = random() * 100;
this.oscillationSpeed = (random() * 2 + 2);
this.oscillationDistance = (random() * 40 + 40);
this.ySpeed = (random() * 40 + 80);
for (var i = 0; i < this.particleCount; i++) {
  this.particles[i] = new EulerMass(_x, _y - i * this.particleDist, this.particleMass, this.particleDrag);
}
this.Update = function(_dt) {
  var i = 0;
  this.time += _dt * this.oscillationSpeed;
  this.position.y += this.ySpeed * _dt;
  this.position.x += cos(this.time) * this.oscillationDistance * _dt;
  this.particles[0].position = this.position;
  var dX = this.prevPosition.x - this.position.x;
  var dY = this.prevPosition.y - this.position.y;
  var delta = sqrt(dX * dX + dY * dY);
  this.prevPosition = new Vector2(this.position.x, this.position.y);
  for (i = 1; i < this.particleCount; i++) {
    var dirP = Vector2.Sub(this.particles[i - 1].position, this.particles[i].position);
    dirP.Normalize();
    dirP.Mul((delta / _dt) * this.velocityInherit);
    this.particles[i].AddForce(dirP);
  }
  for (i = 1; i < this.particleCount; i++) {
    this.particles[i].Integrate(_dt);
  }
  for (i = 1; i < this.particleCount; i++) {
    var rp2 = new Vector2(this.particles[i].position.x, this.particles[i].position.y);
    rp2.Sub(this.particles[i - 1].position);
    rp2.Normalize();
    rp2.Mul(this.particleDist);
    rp2.Add(this.particles[i - 1].position);
    this.particles[i].position = rp2;
  }
  if (this.position.y > ConfettiRibbon.bounds.y + this.particleDist * this.particleCount) {
    this.Reset();
  }
}
this.Reset = function() {
  this.position.y = -random() * ConfettiRibbon.bounds.y;
  this.position.x = random() * ConfettiRibbon.bounds.x;
  this.prevPosition = new Vector2(this.position.x, this.position.y);
  this.velocityInherit = random() * 2 + 4;
  this.time = random() * 100;
  this.oscillationSpeed = random() * 2.0 + 1.5;
  this.oscillationDistance = (random() * 40 + 40);
  this.ySpeed = random() * 40 + 80;
  var ci = round(random() * (colors.length - 1));
  this.frontColor = colors[ci][0];
  this.backColor = colors[ci][1];
  this.particles = new Array();
  for (var i = 0; i < this.particleCount; i++) {
    this.particles[i] = new EulerMass(this.position.x, this.position.y - i * this.particleDist, this.particleMass, this.particleDrag);
  }
};
this.Draw = function(_g) {
  for (var i = 0; i < this.particleCount - 1; i++) {
    var p0 = new Vector2(this.particles[i].position.x + this.xOff, this.particles[i].position.y + this.yOff);
    var p1 = new Vector2(this.particles[i + 1].position.x + this.xOff, this.particles[i + 1].position.y + this.yOff);
    if (this.Side(this.particles[i].position.x, this.particles[i].position.y, this.particles[i + 1].position.x, this.particles[i + 1].position.y, p1.x, p1.y) < 0) {
      _g.fillStyle = this.frontColor;
      _g.strokeStyle = this.frontColor;
    } else {
      _g.fillStyle = this.backColor;
      _g.strokeStyle = this.backColor;
    }
    if (i == 0) {
      _g.beginPath();
      _g.moveTo(this.particles[i].position.x * retina, this.particles[i].position.y * retina);
      _g.lineTo(this.particles[i + 1].position.x * retina, this.particles[i + 1].position.y * retina);
      _g.lineTo(((this.particles[i + 1].position.x + p1.x) * 0.5) * retina, ((this.particles[i + 1].position.y + p1.y) * 0.5) * retina);
      _g.closePath();
      _g.stroke();
      _g.fill();
      _g.beginPath();
      _g.moveTo(p1.x * retina, p1.y * retina);
      _g.lineTo(p0.x * retina, p0.y * retina);
      _g.lineTo(((this.particles[i + 1].position.x + p1.x) * 0.5) * retina, ((this.particles[i + 1].position.y + p1.y) * 0.5) * retina);
      _g.closePath();
      _g.stroke();
      _g.fill();
    } else if (i == this.particleCount - 2) {
      _g.beginPath();
      _g.moveTo(this.particles[i].position.x * retina, this.particles[i].position.y * retina);
      _g.lineTo(this.particles[i + 1].position.x * retina, this.particles[i + 1].position.y * retina);
      _g.lineTo(((this.particles[i].position.x + p0.x) * 0.5) * retina, ((this.particles[i].position.y + p0.y) * 0.5) * retina);
      _g.closePath();
      _g.stroke();
      _g.fill();
      _g.beginPath();
      _g.moveTo(p1.x * retina, p1.y * retina);
      _g.lineTo(p0.x * retina, p0.y * retina);
      _g.lineTo(((this.particles[i].position.x + p0.x) * 0.5) * retina, ((this.particles[i].position.y + p0.y) * 0.5) * retina);
      _g.closePath();
      _g.stroke();
      _g.fill();
    } else {
      _g.beginPath();
      _g.moveTo(this.particles[i].position.x * retina, this.particles[i].position.y * retina);
      _g.lineTo(this.particles[i + 1].position.x * retina, this.particles[i + 1].position.y * retina);
      _g.lineTo(p1.x * retina, p1.y * retina);
      _g.lineTo(p0.x * retina, p0.y * retina);
      _g.closePath();
      _g.stroke();
      _g.fill();
    }
  }
}
this.Side = function(x1, y1, x2, y2, x3, y3) {
  return ((x1 - x2) * (y3 - y2) - (y1 - y2) * (x3 - x2));
}
}
ConfettiRibbon.bounds = new Vector2(0, 0);
confetti = {};
confetti.Context = function(id) {
var i = 0;
var canvas = document.getElementById(id);
var canvasParent = canvas.parentNode;
var canvasWidth = canvasParent.offsetWidth;
var canvasHeight = canvasParent.offsetHeight;
canvas.width = canvasWidth * retina;
canvas.height = canvasHeight * retina;
var context = canvas.getContext('2d');
var interval = null;
var confettiRibbons = new Array();
ConfettiRibbon.bounds = new Vector2(canvasWidth, canvasHeight);
for (i = 0; i < confettiRibbonCount; i++) {
  confettiRibbons[i] = new ConfettiRibbon(random() * canvasWidth, -random() * canvasHeight * 2, ribbonPaperCount, ribbonPaperDist, ribbonPaperThick, 45, 1, 0.05);
}
var confettiPapers = new Array();
ConfettiPaper.bounds = new Vector2(canvasWidth, canvasHeight);
for (i = 0; i < confettiPaperCount; i++) {
  confettiPapers[i] = new ConfettiPaper(random() * canvasWidth, random() * canvasHeight);
}
this.resize = function() {
  canvasWidth = canvasParent.offsetWidth;
  canvasHeight = canvasParent.offsetHeight;
  canvas.width = canvasWidth * retina;
  canvas.height = canvasHeight * retina;
  ConfettiPaper.bounds = new Vector2(canvasWidth, canvasHeight);
  ConfettiRibbon.bounds = new Vector2(canvasWidth, canvasHeight);
}
this.start = function() {
  this.stop()
  var context = this;
  this.update();
}
this.stop = function() {
  cAF(this.interval);
}
this.update = function() {
  var i = 0;
  context.clearRect(0, 0, canvas.width, canvas.height);
  for (i = 0; i < confettiPaperCount; i++) {
    confettiPapers[i].Update(duration);
    confettiPapers[i].Draw(context);
  }
  for (i = 0; i < confettiRibbonCount; i++) {
    confettiRibbons[i].Update(duration);
    confettiRibbons[i].Draw(context);
  }
  this.interval = rAF(function() {
    confetti.update();
  });
}
};
var confetti = new confetti.Context('confetti');
confetti.start();
window.addEventListener('resize', function(event){
confetti.resize();
});
});
</script>
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
  height: 110px;
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
  font-size: 3rem;  
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
    </div>
  </div>

  <div class="buttons">
    <button id="spinner">Play</button>
    <button id="reseter">Reset</button>
  </div>
</div>

<script>
  (function () {
  const items = [
    ['1','2','3','4','0'],
    ['1','2','3','4','5','6','7','8','9','0'],
    ['1','2','3','4','5','6','7','8','9','0'],
  ];
  const doors = document.querySelectorAll('.door');

  document.querySelector('#spinner').addEventListener('click', spin);
  document.querySelector('#reseter').addEventListener('click', init);

  function init(firstInit = true, groups = 2, duration = 1) {
    for (const door of doors) {
      if (firstInit) {
        door.dataset.spinned = '0';
      } else if (door.dataset.spinned === '1') {
        return;
      }

      const boxes = door.querySelector('.boxes');
      const boxesClone = boxes.cloneNode(false);
      const pool = ['❓'];

      if (!firstInit) {
        const arr = [];
        for (let n = 0; n < (groups > 0 ? groups : 1); n++) {
          console.log('n', n, items[n])
          arr.push(...items[n]);
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
    init(false, 3, 2);
    
    for (const door of doors) {
      const boxes = door.querySelector('.boxes');
      const duration = parseInt(boxes.style.transitionDuration);
      boxes.style.transform = 'translateY(0)';
      await new Promise((resolve) => setTimeout(resolve, duration * 100));
    }
  }

  function shuffle([...arr]) {
    let m = arr.length;
    while (m) {
      const i = Math.floor(Math.random() * m--);
      [arr[m], arr[i]] = [arr[i], arr[m]];
    }   

    console.log('arr', arr)

    return arr;

  }

  init();
})();
</script>
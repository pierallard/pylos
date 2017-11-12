<html>
    <?php
    const BOWL_SIZE = 100;
    const SIZE = 4;

    function getLeft($x, $y, $z) {
        return $x * BOWL_SIZE * 0.8 + $y * BOWL_SIZE * 0.6 + ($z) * BOWL_SIZE * 0.7;
    }

    function getTop($x, $y, $z) {
        return (SIZE - $z + 1) * BOWL_SIZE * 0.65 + $x * BOWL_SIZE * 0.3 - $y * BOWL_SIZE * 0.3;
    }

    function getZindex($x, $y, $z) {
        return 100 + $x * 100 - $y * 10 + $z;
    }
    ?>
    <head>
        <style>
            .bowl {
                position: absolute;
                border: 1px solid #ccc;
                border-radius: 1000px;
                width: <?php echo(BOWL_SIZE) ?>px;
                height: <?php echo(BOWL_SIZE) ?>px;
                line-height: <?php echo (BOWL_SIZE) ?>px;
                text-align: center;
                background: white;
                display: none;
            }

            .bowl--possible {
                display: block;
                opacity: 0.5;
            }

            .bowl--possible:hover {
                opacity: 1;
                cursor: pointer;
            }

            .bowl--player1 {
                display:block;
                background: lightyellow;
            }

            .bowl--player2 {
                display: block;
                background: darkred;
            }
        </style>
        <script>
            var conn = new WebSocket('ws://localhost:8080');

            function update_clickable(actions) {
                console.log(actions);
                var bowls = document.getElementsByClassName('bowl');
                for (var i = 0; i < bowls.length; ++i) {
                    var bowl = bowls[i];
                    var x = parseInt(bowl.dataset.x);
                    var y = parseInt(bowl.dataset.y);
                    var z = parseInt(bowl.dataset.z);
                    var found = false;
                    actions.forEach(function (action) {
                        if (parseInt(action['x']) === x && parseInt(action['y']) === y && parseInt(action['z']) === z) {
                            found = true;
                        }
                    });

                    if (found) {
                        bowl.classList.add('bowl--possible');
                        bowl.onclick = function (e) {
                            conn.send(JSON.stringify({ x: e.target.dataset.x, y: e.target.dataset.y, z: e.target.dataset.z }));
                        }
                    } else {
                        bowl.classList.remove('bowl--possible');
                        bowl.onclick = null;
                    }
                }
            }

            function update_board(board) {
                var bowls = document.getElementsByClassName('bowl');
                for (var i = 0; i < bowls.length; ++i) {
                    var bowl = bowls[i];
                    var x = parseInt(bowl.dataset.x);
                    var y = parseInt(bowl.dataset.y);
                    var z = parseInt(bowl.dataset.z);
                    board.forEach(function (item) {
                        if ((item['x'] === x) && (item['y'] === y) && (item['z'] === z)) {
                            if (item['value'] === null) {
                                bowl.classList.remove('bowl--player1');
                                bowl.classList.remove('bowl--player2');
                            } else if (item['value'] === 0) {
                                bowl.classList.add('bowl--player1');
                                bowl.classList.remove('bowl--player2');
                            } else if (item['value'] === 1) {
                                bowl.classList.remove('bowl--player1');
                                bowl.classList.add('bowl--player2');
                            }
                        }
                    })
                }
            }

            conn.onopen = function(e) {
                console.log("Connection established!");
            };

            conn.onmessage = function(e) {
                const json = JSON.parse(e.data);
                if (json.event) {
                    if (json.event === 'connected') {
                        document.getElementById('message').innerHTML = 'Connected!';
                    } else if (json.event === 'game_joined') {
                        document.getElementById('message').innerHTML = 'Game joined! Waiting for players';
                    } else if (json.event === 'game_started') {
                        document.getElementById('message').innerHTML = 'Game started!';
                    } else if (json.event === 'pick_bowl') {
                        document.getElementById('message').innerHTML = 'Please pick a bowl';
                        update_clickable(json.actions);
                    } else if (json.event === 'put_bowl') {
                        document.getElementById('message').innerHTML = 'Please put the bowl';
                        update_clickable(json.actions);
                    } else if (json.event === 'waiting') {
                        document.getElementById('message').innerHTML = 'Waiting for other player';
                        update_clickable([]);
                    } else if (json.event === 'board_changed') {
                        update_board(json.board);
                    }
                    console.log('event ! ' + json.event);
                }
                else {
                    console.log('Message received! ' + e.data);
                }
            };

            conn.onerror = function () {
                console.log('Error');
            };
        </script>
    </head>
    <body>
    <div id="message"></div>
    <?php

    for ($z = 0; $z < SIZE; $z++) {
        for ($x = 0; $x < SIZE - $z; $x++) {
            for ($y = 0; $y < SIZE - $z; $y++) {
                echo(sprintf('<div class="bowl" data-x="%d" data-y="%d" data-z="%d" style="left:%dpx;top:%dpx;z-index:%d"></div>', $x, $y, $z, getLeft($x,$y,$z), getTop($x,$y,$z), getZindex($x, $y, $z)));
            }
        }
    }

    ?>
    <div class="bowl" data-x="-1" data-y="-1" data-z="-1" style="left: 10px; top: 35px;">reserve</div>
    </body>
</html>

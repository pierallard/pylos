<html>
    <?php

    require_once('../vendor/autoload.php');

    const BOWL_SIZE = 100;

    function getLeft($x, $y, $z) {
        return $x * BOWL_SIZE * 0.8 + $y * BOWL_SIZE * 0.6 + ($z) * BOWL_SIZE * 0.7;
    }

    function getTop($x, $y, $z) {
        return (\Pylos\Board::SIZE - $z + 1) * BOWL_SIZE * 0.65 + $x * BOWL_SIZE * 0.3 - $y * BOWL_SIZE * 0.3;
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

            #undo {
                float: right;
            }

            #undo.hidden {
                display: none;
            }
        </style>
        <script>
            let conn = new WebSocket('ws://localhost:8080');

            function update_clickable(actions) {
                const bowls = document.getElementsByClassName('bowl');
                for (let i = 0; i < bowls.length; ++i) {
                    const bowl = bowls[i];
                    const x = parseInt(bowl.dataset.x);
                    const y = parseInt(bowl.dataset.y);
                    const z = parseInt(bowl.dataset.z);

                    let foundAction = null;
                    actions.forEach(function (action) {
                        if (parseInt(action['x']) === x && parseInt(action['y']) === y && parseInt(action['z']) === z) {
                            foundAction = action;
                        }
                    });

                    if (null !== foundAction) {
                        bowl.classList.add('bowl--possible');
                        bowl.onclick = function (e) {
                            conn.send(JSON.stringify({
                                action: foundAction['action'],
                                x: e.target.dataset.x,
                                y: e.target.dataset.y,
                                z: e.target.dataset.z
                            }));
                        }
                    } else {
                        bowl.classList.remove('bowl--possible');
                        bowl.onclick = null;
                    }
                }
            }

            function update_undo(value) {
                let undo = document.getElementById('undo');
                if (value) {
                    undo.classList.remove('hidden');
                    undo.onclick = function (e) {
                        conn.send(JSON.stringify({
                            action: 'undo'
                        }));
                    };
                } else {
                    undo.classList.add('hidden');
                    undo.onclick = null;
                }
            }

            function update_board(board) {
                const bowls = document.getElementsByClassName('bowl');
                for (let i = 0; i < bowls.length; ++i) {
                    const bowl = bowls[i];
                    const x = parseInt(bowl.dataset.x);
                    const y = parseInt(bowl.dataset.y);
                    const z = parseInt(bowl.dataset.z);
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

            function log(message) {
                document.getElementById('message').innerHTML = message;
            }

            conn.onmessage = function(e) {
                const json = JSON.parse(e.data);
                if (json.event) {
                    if (json.event === '<?php echo \Pylos\Event::CONNECTED; ?>') {
                        log('Connected!');
                    } else if (json.event === '<?php echo \Pylos\Event::GAME_JOINED; ?>') {
                        log('Game joined! Waiting for players');
                    } else if (json.event === '<?php echo \Pylos\Event::ACTIONS; ?>') {
                        log(json.actions.length > 0 ? 'Your turn' : 'Wait for your turn!');
                        update_clickable(json.actions);
                    } else if (json.event === '<?php echo \Pylos\Event::UPDATE_UNDO; ?>') {
                        update_undo(json.value);
                    } else if (json.event === '<?php echo \Pylos\Event::BOARD; ?>') {
                        update_board(json.board);
                    }
                }
            };
        </script>
    </head>
    <body>
    <div id="message"></div>
    <div id="undo" class="hidden">Undo !</div>
    <?php

    for ($z = 0; $z < \Pylos\Board::SIZE; $z++) {
        for ($x = 0; $x < \Pylos\Board::SIZE - $z; $x++) {
            for ($y = 0; $y < \Pylos\Board::SIZE - $z; $y++) {
                echo(sprintf('<div class="bowl" data-x="%d" data-y="%d" data-z="%d" style="left:%dpx;top:%dpx;z-index:%d"></div>', $x, $y, $z, getLeft($x,$y,$z), getTop($x,$y,$z), getZindex($x, $y, $z)));
            }
        }
    }

    ?>
    <div class="bowl" data-x="-1" data-y="-1" data-z="-1" style="left: 10px; top: 35px;">reserve</div>
    </body>
</html>

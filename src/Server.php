<!doctype html>
<html>
    <body>
        <form method="post" action="">
            <input type="text" name="foo">
            <button type="submit"></button>
        </form>
        <?php

            if (isset($_POST['foo'])) {
                echo "<h1>{$_POST['foo']}</h1>";
            }

        ?>
    </body>
</html>


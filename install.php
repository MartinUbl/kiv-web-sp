<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <title>WEBKONF - instalace</title>

    <link rel="stylesheet" href="css/install.css" />
</head>
<body>

    <div id="container">

        <h1>Instalace WEBKONF</h1>

<?php

/**
 * Function for proceeding with installation
 * @param string $host
 * @param string $user
 * @param string $password
 * @param string $dbsystem
 * @param string $dbname
 * @param boolean $testdata
 * @param string $pagetitle
 * @return boolean
 */
function proceedInstall($host, $user, $password, $dbsystem, $dbname, $testdata, $pagetitle)
{
    // prepare DB connection
    $dsn = $dbsystem.':dbname='.$dbname.';host='.$host;
    try
    {
        // also force UTF-8
        $db = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        // and exception mode for errors
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $ee)
    {
        echo "Nelze se připojit k serveru: ".$ee->getMessage();
        return false;
    }

    // prepare config
    $cfgfile = file_get_contents(__DIR__.'/install/config.inc.template.php');

    // prepare substitution array
    $subarray = array(
        'DBSYSTEM' => $dbsystem,
        'DBHOST' => $host,
        'DBUSER' => $user,
        'DBPASS' => $password,
        'DBNAME' => $dbname,
        'PAGETITLE' => $pagetitle
    );

    // replace every key-value with regular value
    foreach ($subarray as $key => $value)
        $cfgfile = str_replace('${'.$key.'}', $value, $cfgfile);

    // writeout config
    file_put_contents(__DIR__.'/app/config/config.inc.php', $cfgfile);

    // try to install structure
    try
    {
        $db->exec(file_get_contents(__DIR__.'/install/db.structure.sql'));
    }
    catch (PDOException $ee)
    {
        echo "Nelze vytvořit databázovou strukturu: <br/>".$ee->getMessage();
        return false;
    }

    echo "Konfigurační soubor byl připraven a databázová struktura vytvořena!";

    // if user requested testing data to be installed
    if ($testdata)
    {
        // check if testdata SQL file exists
        if (file_exists(__DIR__.'/install/db.testdata.sql'))
        {
            // install it
            $db->exec(file_get_contents(__DIR__.'/install/db.testdata.sql'));

            // copy test PDFs
            for ($i = 1; $i <= 6; $i++)
                if (file_exists(__DIR__.'/install/testdata/testdata'.$i.'.pdf'))
                    copy(__DIR__.'/install/testdata/testdata'.$i.'.pdf', __DIR__.'/uploads/testdata'.$i.'.pdf');

            echo "<br/>Testovací data byla nahrána!";
        }
        else
        {
            echo "<br/>Nelze otevřít soubor db.testdata.sql, testovací data nebudou nahrána.";
        }
    }

    // success
    return true;
}

// at first, verify if we installed system before (determined by presence of config file)
if (file_exists(__DIR__.'/app/config/config.inc.php'))
{
    echo "Systém již byl nainstalován! Pro opětovnou instalaci vymažte konfigurační soubor config.inc.php z podadresáře app/config/";
}
// check critical files presence
else if (!file_exists(__DIR__.'/install/config.inc.template.php') ||
    !file_exists(__DIR__.'/install/db.structure.sql'))
{
    echo "V adresáři install/ se nenachází všechny potřebné soubory. Zkontroluje, zdali adresář obsahuje tyto soubory, a jsou nastavena potřebná práva: <br/>";
    echo "config.inc.template.php<br />";
    echo "db.structure.sql<br />";
}
// if the form was submitted already
else if (isset($_POST['dbhost']) && isset($_POST['dbuser']) && isset($_POST['dbpass']) && isset($_POST['dbsystem']) && isset($_POST['dbname']) && isset($_POST['pagetitle']))
{
    // lower error reporting due to possible inconsistencies in PHP version - this will suppress possible deprecation warnings and several notices
    error_reporting(E_ERROR);

    if (!proceedInstall($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbsystem'], $_POST['dbname'], isset($_POST['testdata']), $_POST['pagetitle']))
    {
        echo '<a class="button" href="./install.php" title="Nové zadání">Nová instalace</a>';
    }
    else
    {
        echo '<a class="button" href="." title="Přejít na stránky">Jít na WEBKONF portál</a>';
    }
}
// offer installation
else
{
?>

    <form action="install.php" method="POST">
        <label>
            DB Systém:<br/>
            <select name="dbsystem">
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
            </select>
        </label>
        <br />
        <label>
            DB Server:<br/>
            <input type="text" name="dbhost" />
        </label>
        <br />
        <label>
            DB Uživatel:<br/>
            <input type="text" name="dbuser" />
        </label>
        <br />
        <label>
            DB Heslo:<br/>
            <input type="password" name="dbpass" />
        </label>
        <br />
        <label>
            Jméno databáze:<br/>
            <input type="text" name="dbname" />
        </label>
        <br />
        <label>
            Výchozí titulek stránek:<br/>
            <input type="text" name="pagetitle" value="KIV/WEB - konferenční systém" />
        </label>
        <br />
        <label>
            <input type="checkbox" name="testdata" />
            Nahrát testovací data
        </label>
        <br />
        <input type="submit" value="Instalovat" />
    </form>
    
<?php
}
?>

        </div>

</body>
</html>



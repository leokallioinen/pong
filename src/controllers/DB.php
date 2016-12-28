<?php
namespace pong\controllers;
require __DIR__ . '/../../vendor/autoload.php';
include("../inc/db.inc");

class DB
{
    private $db;

    function __construct(){
        $this->connect();
    }

    function __destruct(){

    }


    /***********
     * Private
     ***********/
    private function connect(){
    //        $this -> db = pg_connect("dbname=".DB_DATABASE." host=".DB_SERVER." user=".DB_USERNAME." password=".
        $this -> db = pg_connect("dbname=".DB_DATABASE." user=".DB_USERNAME." password=".
            DB_PASSWORD." options='--client_encoding=UTF8'");
    }

    private function sql($query)
    {
            // echo "QDEBUG: $query<br/>";
        $result = pg_query($this->db,$query);
            // echo "RDEBUG: ".print_r($result,true)."<br/>";
        return $result;
    }

    public function sql_result_array($query)
    {
        $result = $this -> sql($query);
        return pg_fetch_all($result);
    }

    private function sql_get_single($query)
    {
        $result = $this -> sql($query);
        if (!pg_num_rows($result)) {
            return 0;
        } else {
            return pg_fetch_result($result, 0, 0);
        }
    }

    private function sql_fetch_first_column($query)
    {
        $result = $this -> sql($query);
        if ($result)
            return pg_fetch_all_columns($result);
        else
            return 0;
    }

    /********************
     * Public interface 
     *******************/
    public function get_elo($player){
        $query = "SELECT elo from users where name='".trim(strtolower(pg_escape_string($player)))."'";
        return $this->sql_get_single($query);
    }
    public function get_stats($lim = 0){
        if($lim)
            $sql = "SELECT name,elo,games,wins,losses from users order by elo desc limit ".(int)$lim;
        else
            $sql = "SELECT name,elo,games,wins,losses from users order by elo desc";
        return $this->sql_result_array($sql);
    }

    public function do_insert_game($record) 
    {        
        $query = pg_insert($this->db, "games", $record, PGSQL_DML_STRING);
        $result = $this->sql($query);
        return $result;
    }
    public function is_admin($player) 
    {
        $query = "SELECT admin FROM users WHERE name='".trim(strtolower(pg_escape_string($player)))."'";
        $admin = $this->sql_get_single($query);
        return $admin=='t';
    }
    public function set_admin($player,$b)
    {
        $query = "UPDATE users SET admin='".($b?"t":"f")."' WHERE name='".trim(strtolower(pg_escape_string($player)))."'";
        $this->sql($query);
        return;
    }
    public function player_exists($player)
    {
        $query = "SELECT count(*) from users where name='".trim(strtolower(pg_escape_string($player)))."'";
        return $this->sql_get_single($query);
    }
    public function insert_new_player($player)
    {
        $query = "INSERT INTO users (name,elo,games) VALUES ('".trim(strtolower(pg_escape_string($player)))."',0,0)";
        $res = $this->sql_get_single($query);
    }
    public function update_elo($player,$elo){
        if($player && isset($elo)){
            $query = "UPDATE users SET elo=$elo, games = games + 1 WHERE name='".pg_escape_string($player)."'";
            return $this->sql($query);
        }
    }
    public function add_win($player){
        $query = "UPDATE users SET wins = wins + 1 WHERE name='".trim(strtolower(pg_escape_string($player)))."'";
        $this->sql($query);
    }
    public function add_loss($player){
        $query = "UPDATE users SET losses = losses + 1 WHERE name='".trim(strtolower(pg_escape_string($player)))."'";
        $this->sql($query);
    }
    public function update_games(){
        $names = $this->sql_result_array("SELECT name FROM users");
        foreach ($names as $n)
        {
            $name=$n["name"];
            $wins = $this->sql_get_single("SELECT count(winner) FROM games WHERE winner='$name'");
            $total = $this->sql_get_single("SELECT count(*) FROM games WHERE player1='$name' OR player2='$name'");
            $losses = $total - $wins;
            $this->sql("UPDATE users SET games=$games,wins=$wins,losses=$losses WHERE name='$name'");
            echo "$name games:".print_r($total,true)." wins:$wins losses:$losses\n";
        }
    }
}

?>

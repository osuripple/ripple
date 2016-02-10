<?php
// Get old score data
$scores = $GLOBALS["db"]->fetchAll("SELECT * FROM scores");

// remove last space from username
for($i = 0; $i < count($scores); $i++)
{
    $scores[$i]["username"] = rtrim($scores[$i]["username"], " ");
    $GLOBALS["db"]->execute("UPDATE scores SET username = ? WHERE id = ?", array($scores[$i]["username"], $scores[$i]["id"]));
}

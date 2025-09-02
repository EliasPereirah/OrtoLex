<?php
namespace App;
use PDO, PDOException, PDOStatement;

class Database
{
    private PDO $PDO;
    public function __construct(string $dbname = '')
    {
        if(!$dbname){
            $dbname = DB_CONFIG['dbname'];
        }
        try {
            $this->PDO = new PDO(DB_CONFIG['driver'].":host=".DB_CONFIG['host'].";dbname=".$dbname, DB_CONFIG['username'], DB_CONFIG['password'], DB_CONFIG['options']);
            $this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            if(PRODUCTION){
                exit("Ops, houve um erro na conexão com o BD: <b>{$e->getCode()}</b>");
            }else{
                exit("Ops, houve um erro na conexão com o BD: <b>{$e->getMessage()}</b>");
            }
        }
    }

    public function insert($sql, array $binds): bool
    {
        try {
            $stmt = $this->PDO->prepare($sql);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "A entrada já existe<br>";
            } else {
                throw $e;
            }
            return false;
        }
    }


    public function select(string $sql, array $binds): bool|PDOStatement
    {
        $stmt = $this->PDO->prepare($sql);
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    public function update(string $sql, array $binds): int
    {
        $stmt = $this->PDO->prepare($sql);
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete(string $sql, array $binds): int
    {
        $stmt = $this->PDO->prepare($sql);
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }
}

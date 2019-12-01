<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('<h1>KIOSK API TestPage</h1>');
        return $response;
    });

    //Fetch all issues from the data base
    $app->get('/issues', function (Request $request, Response $response) {
        $query = "SELECT * FROM issue";
        try{
            $db = getConnection();
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($result));
            return $response;
        } catch(PDOException $PDOException){
            $response->getBody()->write(responseToJson(1,"error"));
            return $response;
        }
    });

    //Increment the selected issueCount count
    $app->post('/issue/increaseCount/{issueID}',function(Request $request,Response $response,array $args){

        $issueId = $args['issueID'];
        $sql  = "UPDATE issue SET isuCount = isuCount + 1 WHERE kioks_db.issue.isuId = :iid";

        try{
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindValue('iid',$issueId);
            $stmt->execute();
            $json = responseToJson(0,"success");
            $response->getBody()->write($json);
            return $response;
        } catch (PDOException $pdoe){
            $response->getBody()->write(responseToJson(1,"error"));
            return $response;
        }
    });

    //GEt issue by ID
    $app->post('/issue/getIssue/{isuId}',function(Request $request,Response $response, array $args){
       $issueID = $args['isuId'];
       $sql = "SELECT isuName, isuDscp FROM kioks_db.issue WHERE kioks_db.issue.isuId = :id";

       try{
           $db = getConnection();
           $stmt = $db->prepare($sql);
           $stmt->bindParam('id',$issueID);
           $stmt->execute();
           $result = $stmt->fetchAll(PDO::FETCH_OBJ);
           $response->getBody()->write(json_encode($result));
           return $response;
       } catch (PDOException $pdoe){
           $response->getBody()->write(responseToJson(1,"error"));
           return $response;
       }
    });

    //Staff update the issue when finished working with the customer
    $app->post('/issue/submitIssueByStaff', function(Request $request, Response $response, array  $args){
        $body = $request->getParsedBody();
        $staffID = $body['sId'];
        $customerID = $body['cId'];
        $isuID = $body['isId'];
        $isuProblem = $body['isu'];

        $query = "INSERT INTO kioks_db.issuerecord (issuerecord.sID,issuerecord.cID,issuerecord.isuID,issuerecord.isuInfo) VALUES 
                    (:sid,:cid,:isId,:isu)";

        try{
            $db = getConnection();
            $stmt = $db->prepare($query);
            $stmt->bindParam('sid',$staffID);
            $stmt->bindParam('cid',$customerID);
            $stmt->bindParam('isId',$isuID);
            $stmt->bindParam('isu',$isuProblem);
            $stmt->execute();

            $response->getBody()->write(responseToJson(0,"success"));
            return $response;
        } catch (PDOException $pdoe){
            $response->getBody()->write(responseToJson(1,"error"));
            return $response;
        }
    });


    /**
     * Main function to get the connection
     * @return PDO|null
     */
    function getConnection()
    {
        $dbhost = "localhost";
        $dbUsername = "snTestDb";
        $dbPass = "root";
        $dbName = "kioks_db";

        try{
            $db = new PDO("mysql:host=$dbhost;dbname=$dbName",$dbUsername,$dbPass);
            $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

            return $db;
        }catch(PDOException $pdoe)
        {
            return null;
        }
    }

    /**
     * Writting response message and response code in array and return as JSON format
     * @param $responseCode
     * @param $responseMessage
     * @return false|string
     */
    function responseToJSON($responseCode, $responseMessage){
        $response_array['status'] = $responseMessage;
        $response_array['code'] = $responseCode;
        return json_encode($response_array);
    }
};


<?php
# @name: oabutton.php
# @version: 0.2
# @license: GNU General Public License version 3 (GPLv3) <https://www.gnu.org/licenses/gpl-3.0.en.html>
# @purpose: Accept data from the OA Button and submit ILL requests ("Resource Sharing Borrowing Requests")to Imperial College Library's Ex Libris Alma library management system
# @author: Simon Barron <s.barron@imperial.ac.uk>
# @acknowledgements: 
?>
<?php
    #define variables and fix the input from the form before sending it to XML
    $almaurl="https://api-eu.hosted.exlibrisgroup.com";
    $apikey="xxxxxxxxxxxxxx";
    
    #read Json from file provided by OA Button
    $json = json_decode(file_get_contents('php://input'), true);

    #search Alma for the user's email address and retrieve username
    $ch = curl_init();
    $url = $almaurl.'/almaws/v1/users/';
    $queryParams = '?' . urlencode('limit') . '=' . urlencode('10') . '&' . urlencode('offset') . '=' . urlencode('0') . '&' . urlencode('q') . '=' . urlencode('email~') . $json['id'] . '&' . urlencode('order_by') . '=' . urlencode('last_name, first_name, primary_id') . '&' . urlencode('apikey') . '=' . urlencode($apikey);
    curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($ch);
    curl_close($ch);

    $user_xml = new SimpleXMLElement($response);
    $user_id=$user_xml->user->primary_id;
    $user_id_url=urlencode($user_id);
    
    #create Json for submitting to Alma
    $jsonarray=array(
        'format'=>array('value'=>'DIGITAL','desc'=>'Digital'),
        'title'=>$json['title'],
        'year'=>null,
        'journal_title'=>null,
        'last_interest_date'=>date("Y-m-d", strtotime("+1 week")) . "Z",
        'citation_type'=>array('value'=>'CR','desc'=>'Article'),
        'agree_to_copyright_terms'=>'true'
        );
    $requestjson=json_encode($jsonarray);
    
    #submit Json using cURL to Alma's 'create user request for resource sharing' function in the API
    # https://developers.exlibrisgroup.com/alma/apis/users/POST/gwPcGly021r0XQMGAttqcPPFoLNxBoEZ48s5U7d2yuoXwmuEh++anMi4mohwnX9VTzJnp13lw54=/0aa8d36f-53d6-48ff-8996-485b90b103e4

    $ch = curl_init();
    $url = $almaurl.'/almaws/v1/users/' . $user_id_url . '/resource_sharing_requests';
    $queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
    curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestjson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $response = curl_exec($ch);
    curl_close($ch);

    var_dump($response);
?>
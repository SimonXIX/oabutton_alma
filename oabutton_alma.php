<?php
# @name: oabutton_alma.php
# @version: 0.5
# @license: GNU General Public License version 3 (GPLv3) <https://www.gnu.org/licenses/gpl-3.0.en.html>
# @purpose: Accept data from the OA Button and submit ILL requests ("Resource Sharing Borrowing Requests")to Imperial College Library's Ex Libris Alma library management system
# @author: Simon Barron <s.barron@imperial.ac.uk>
# @acknowledgements: 
?>
<?php
    #define variables
    $almaurl="https://api-eu.hosted.exlibrisgroup.com";
    $apikey="xxxxxxxxxxxxxx";
    
    #test lines for reading from a Json file
    #$jsondata = file_get_contents('test.json');
    #$json = json_decode($jsondata, true);
    
    #read Json from file POSTed by OA Button
    $json = json_decode(file_get_contents('php://input'), true);

    #search Alma for the user's email address and retrieve username
    $url = $almaurl.'/almaws/v1/users/';
    $queryParams = '?' . urlencode('limit') . '=' . urlencode('10') . '&' . urlencode('offset') . '=' . urlencode('0') . '&' . urlencode('q') . '=' . urlencode('email~') . $json['id'] . '&' . urlencode('order_by') . '=' . urlencode('last_name, first_name, primary_id') . '&' . urlencode('apikey') . '=' . urlencode($apikey);
    $fullurl = $url.$queryParams;
    
    #GET using cURL
    #$ch = curl_init();
    #curl_setopt($ch, CURLOPT_URL, $fullurl);
    #curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    #curl_setopt($ch, CURLOPT_HEADER, FALSE);
    #curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    #$response = curl_exec($ch);
    #curl_close($ch);

    #GET without using cURL
    $stream_options = array(
        'http' => array(
            'method'  => 'GET'));
    $context  = stream_context_create($stream_options);
    $response = file_get_contents($fullurl, null, $context);
    
    $user_xml = new SimpleXMLElement($response);
    
    if ($user_xml['total_record_count'] <= 0){
        # send a failure email to the user
        $to = $json['id'];
        $from = "library@imperial.ac.uk";
        $subject = "Imperial Open Access Button request failed";
        $message = "Hi,". "<br /><br />" . "You tried to submit a request for Document Delivery at Imperial College Library through the Open Access Button. Unfortunately your email address was not found in our library systems. Please try again using your @imperial.ac.uk institutional email address. Contact <a href='mailto:library@imperial.ac.uk'>library@imperial.ac.uk</a> if you continue to encounter problems." . "<br /><br />" . "Kind regards," . "<br /><br />" . "Library Services";

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= "From:" . $from;
        mail($to,$subject,$message,$headers);
    }
    else{    
        $user_id=$user_xml->user[0]->primary_id;
        $user_id_url=urlencode(strtolower($user_id));
    
        #create Json for submitting to Alma
        $jsonarray=array(
            'format'=>array('value'=>'DIGITAL','desc'=>'Digital'),
            'title'=>$json['title'],
            'year'=>'yyyy',
            'journal_title'=>$json['journal']['title'],
            #'last_interest_date'=>date("Y-m-d", strtotime("+1 week")) . "Z",
            'citation_type'=>array('value'=>'CR','desc'=>'Article'),
            'note'=>'doi='.$json['doi'],
            'agree_to_copyright_terms'=>'true'
            );
        $requestjson=json_encode($jsonarray);
    
        #submit Json to Alma's 'create user request for resource sharing' function in the API
        # https://developers.exlibrisgroup.com/alma/apis/users/POST/gwPcGly021r0XQMGAttqcPPFoLNxBoEZ48s5U7d2yuoXwmuEh++anMi4mohwnX9VTzJnp13lw54=/0aa8d36f-53d6-48ff-8996-485b90b103e4

        $url = $almaurl.'/almaws/v1/users/' . $user_id_url . '/resource_sharing_requests';
        $queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
        $fullurl = $url.$queryParams;
    
        #submit using cURL
        #$ch = curl_init();
        #curl_setopt($ch, CURLOPT_URL, $fullurl);
        #curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        #curl_setopt($ch, CURLOPT_HEADER, FALSE);
        #curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        #curl_setopt($ch, CURLOPT_POSTFIELDS, $requestjson);
        #curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        #$response = curl_exec($ch);
        #curl_close($ch);
    
        #submit without using cURL
        $stream_options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json' . "\r\n",
                'content' => $requestjson));

        $context  = stream_context_create($stream_options);
        $response = file_get_contents($fullurl, null, $context);
    
        var_dump($response);
    }
?>

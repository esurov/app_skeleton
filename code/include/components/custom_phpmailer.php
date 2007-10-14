<?

class CustomPHPMailer extends PHPMailer {

    function AddStringImageAttachment(
        $string, 
        $cid, 
        $filename, 
        $encoding = "base64", 
        $type = "application/octet-stream", 
        $content_disposition = "inline"
    ) {
        // Append to $attachment array
        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $string;
        $this->attachment[$cur][1] = $filename;
        $this->attachment[$cur][2] = $filename;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = true; // isString
        $this->attachment[$cur][6] = $content_disposition;
        $this->attachment[$cur][7] = $cid;
    }

}

?>
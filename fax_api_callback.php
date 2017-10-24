<?php
// POST
file_put_contents('api_callback_data.txt', print_r($_POST, true));
/**
 Example values
 array(
    [fax_job_id] => 123456
    [status] => failed
    [msg_code] => no_answer
    [message] => Fax Failed - No Answer from Fax machine
 )
 *
 * Change your records accordingly using the supplied fax_job_id
 */
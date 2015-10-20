<?php
/**
 * Generic push-notification library.
 *
 * @author Kenshiro	Hanamoto
 */
class Gcmpush {

    private static $URL = 'https://gcm-http.googleapis.com/gcm/send';
    private static $REGISTRATION_ID_LIMIT = 1000;

    /**
     * Send one push-notification for many user.
     *
     * @param string $api_key API keys that are issued for each project
     * @param array $registration_ids Destination of push-notification
     * @param mixed $content Various contents of push-notification
     * @param boolean $dry_run Whether to send to the user's terminal(Default false)
     * @throws GcmpushException When error occured
     * @return List of success-count, failure-count and whole of the response
     */
    public function send_multicast($api_key, Array $registration_ids, $content, $dry_run = false)
    {
        // validation
        if (empty($api_key))
        {
            $mesg = "api_key is required.";
            throw new GcmpushException($mesg, 400);
        }
        if (empty($registration_ids))
        {
            $mesg = "registration_ids is required.";
            throw new GcmpushException($mesg, 400);
        }
        if (count($registration_ids) > self::$REGISTRATION_ID_LIMIT)
        {
            $mesg = "registration_ids is too much. Limit is "
                  . self::$REGISTRATION_ID_LIMIT
                  . ". Count of registration_ids = [" . count($registration_ids) . "]";
            throw new GcmpushException($mesg, 400);
        }
        if (empty($content))
        {
            $mesg = "content is required.";
            throw new GcmpushException($mesg, 400);
        }

        $post = array(
            "registration_ids" => $registration_ids,
            "data" => $content,
        );
        // append test flg.
        $post['dry_run'] = $dry_run;

        // send to push notification.
        $ch = curl_init(self::$URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->json_header($api_key));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (! $ret = curl_exec($ch))
        {
            $mesg = 'failed to send push data. registration_ids = [' . implode(',', $registration_ids) . ']';
            throw new GcmpushException($mesg, 500);
        }
        curl_close($ch);

        // make response.
        $response = json_decode($ret, true);
        if (is_null($response))
        {
            $mesg = 'json decode error. response = [' . $ret . ']';
            throw new GcmpushException($mesg, 500);
        }
        $success = (int)isset($response['success']) ? $response['success'] : 0;
        $failure = (int)isset($response['failure']) ? $response['failure'] : 0;

        return array('success' => $success, 'failure' => $failure, 'response' => $response);
    }

    private function json_header($api_key)
    {
        $header = array(
            'Content-Type: application/json',
            'Authorization: key=' . $api_key
        );
        return $header;
    }
}

class GcmpushException extends RuntimeException {}
<?php

use App\Core_file;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;

if (!function_exists('sendMail')) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string $path
	 * @return string
	 */

	function sendMail($data) {
		config(['messagedata' => $data]);
		Mail::send([], [], function ($message) {
			$message->to(config('messagedata')['email'])
				->subject(config('messagedata')['subject'])
				->cc(config('messagedata')['cc'])
				->setBody(config('messagedata')['body']);
		});
	}
}

if (!function_exists('public_path')) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string $path
	 * @return string
	 */
	function public_path($path = '') {
		return env('PUBLIC_PATH', base_path('public')) . ($path ? '/' . $path : $path);
	}
}
if (!function_exists('fileUpload')) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string $path
	 * @return string
	 */
	function fileUpload(Request $request) {
        $fileArray = array();
        $upFile = ($request->file('file'));
        $count = count($upFile);
		$user = (object) ['file' => ""];
		if ($request->hasFile('file')) {
            foreach ($upFile as $file) {
    			$originalFilename = $file->getClientOriginalName();
    			$fileSize = $file->getSize();
    			$originalFilenameArr = explode('.', $originalFilename);
    			$fileExt = end($originalFilenameArr);
    			$destinationPath = storage_path("app/public/files");
    			$cryptName = md5(uniqid().time());
    			$image = $cryptName . '.' . $fileExt;
    			if ($file->move($destinationPath, $image)) {
    				// if (($fileExt == 'jpeg') or ($fileExt == 'png') or ($fileExt == 'gif') or ($fileExt == 'tiff') or ($fileExt == 'jpg')) {
    				// 	Image::make($destinationPath . '/' . $image)->resize(300, 300)->save($destinationPath . '/thumb_' . $image);
    				// }
                    $data['file_name'] = $image;
                    $data['file_path'] = $destinationPath;
                    $data['file_type'] = $fileExt;
                    $data['file_size'] = $fileSize;
                    $data['file_org_name'] = $originalFilename;
                    $fileData = Core_file::create($data);
                    // if ($count==1) {
                    //     return $fileData->file_id;
                    // }
                    // else {
                        $fileArray[] = $fileData->file_id;
                   // }                
			     } else {
				    return 0;
			     }                 
            }
            return $fileArray;
		} else {
			return 0;
		}
	}
}

if (!function_exists('encryptionHelper')) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string $path
	 * @return string
	 */
	function encryptionHelper($array) {
		$i = OT_ZERO;
		foreach ($array as $key => $value) {
			$keyArray[] = $key;
		}
		foreach ($array as $value) {
			$returnArray[$keyArray[$i]] = encrypt($value);
			$i++;
		}
		return $returnArray;
	}
}

if (!function_exists('decryptionHelper')) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string $path
	 * @return string
	 */
	function decryptionHelper($array) {
		foreach ($array[0] as $key => $value) {
			$keyArray[] = $key;
		}
		foreach ($array as $row) {
			$i = OT_ZERO;
			foreach ($row as $cell) {
				try {
					$returnArray[$keyArray[$i]] = decrypt($cell);
				} catch (\Exception $e) {
					$returnArray[$keyArray[$i]] = $cell;
				}
				$i++;
			}
			$newArray[] = $returnArray;
		}
		return $newArray;
	}
}

/**
 * Generate pdf Report
 */
function generateReport($id) {
	$summaryDetails = \DB::table('ticket_print')
		->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_print.tp_ticket_id')
		->leftjoin('public_users', 'public_users.pusr_id', '=', 'tickets.ticket_pusr_id')
		->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id')
		->where('tp_ticket_id', '=', $id)
		->orderBy('tp_id')
		->select('ticket_print.*', 'tickets.*', 'public_users.*', 'destination.*')->get();
	$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>DTPC</title>
                        <style type="text/css">
                            body {
                                font-family: Arial, Helvetica, sans-serif;
                                margin: 0px;
                                padding: 0px;
                            }
                            p,h1,h2,h3,h4,h5,h6{
                                margin: 0px;
                                padding: 0px;
                            }
                            hr {
                                color: #CCC;
                            }
                            </style>
                        </head>
                    <body>';
	foreach ($summaryDetails as $data) {
		$qrData = $data->tp_actual_number . ';' . $data->tp_date;
		$email = $data->customer_email;
		$name = $data->customer_name;
		$body .= '<table width="100%" border="0" cellspacing="0" cellpadding="3">
                <tr>
            <td><img src="https://dtpc-ticket-pwa.web.app/assets/images/logo.png"  width=250></td>
            </tr>
            <tr>
            <td align="center" style="font-size:14px;"><strong>DTPC Wayanad</strong></td>
            </tr>
            <tr>
            <td align="center" style="font-size:13px;"><strong>Wayanad Adventure Camp, ' . $data->dest_name . '</strong><br/>
            ' . $data->dest_place . ' - ' . $data->dest_pincode . ' Ph: ' . $data->dest_phone . '</td>
            </tr>
            <tr>
            <td align="center"><span style=" border:solid 3px #000000; padding-top:10px; padding-bottom:10px;" >' . $data->tp_content . '</span></td>
            </tr>
            <tr>
            <td align="center" ><h4 class="tno">Ticket No. ' . $data->tp_actual_number . '<span style="font-weight: normal;">|' . $data->tp_date . ' ' . $data->tp_time . '</span></h4></td>
            </tr>
            <tr>
            <td align="center"><h3></h3></td>
            </tr>
            <tr>
            <td align="center"><strong>Price ----- Rs. ' . $data->tp_rate . '/-</strong></td>
            </tr>
            <tr>
            <td align="center"><hr/></td>
            </tr>
            <tr>
            <td align="center" style="font-size:14px;">*This ticket is not retainable or refundable<br/>
            **Keep this ticket till you leave the destination</td>
            </tr>
            <tr>
            <td align="center" style="font-size:15px;">
            Email: info@dtpcwayanad.com<br/>
            Website: www.wayanadtourism.org<br/></td>
            </tr>
            <tr>
                <td align="center"><span style="font-size:14px;">Sponsored by</span><br/><img src="https://dtpc-ticket-pwa.web.app/assets/images/canara_logo.png" width=180/></td>
            </tr>
            <tr><td align="center"><span style="font-size:14px;">Sponsored by</span><br/><img src="https://dtpc-ticket-pwa.web.app/assets/images/canara_logo.png" width=180/></td>
            </tr>
            <tr><td align="center"><br/><br/><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $qrData . '">'; //.//QrCode::size(120)->generate($data->tp_actual_number.';'.$data->tp_date).'
		$body .= '</td>
            </tr>
            </table><div style="page-break-before: always;"></div>';
	}

	$body .= '</body>
    </html>';
	$basePath = __DIR__;
	$pathToVendor = substr($basePath, 0, -3) . 'vendor/autoload.php';
	require_once $pathToVendor;
	$mpdf = new \Mpdf\Mpdf();
	$mpdf->WriteHTML($body);
	$fileName = Crypt::encrypt($id);
	$mpdf->Output($fileName . '.pdf', 'F');
	$content = "Hi " . $name . ", Your payment of booking has been successfully completed. Please check the attachment of tickets for visitng. Thank You";
	/**Insert into mail queue */
	$dbArray['subject'] = "DTPC Ticket Booking Summary..!";
	$dbArray['message'] = $content;
	$dbArray['smq_recipient'] = $email;
	DB::table('sms_mail_que')->insert($dbArray);
	$emailId = DB::getPdo()->lastInsertId();
	//Mail file
	$dbArray = array();
	$dbArray['mf_smq_id'] = $emailId;
	$dbArray['mf_file'] = getcwd() . '/' . $fileName . '.pdf';
	DB::table('mail_file')->insert($dbArray);
}

/**
 * Generate pdf Report
 */
function generatepdfforpublicbooking($id, $name = '', $email = '') {
	$summaryDetails = \DB::table('ticket_print')
		->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_print.tp_ticket_id')
		->leftjoin('public_users', 'public_users.pusr_id', '=', 'tickets.ticket_pusr_id')
		->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id')
		->where('tp_ticket_id', '=', $id)
		->orderBy('tp_id')
		->select('ticket_print.*', 'tickets.*', 'public_users.*', 'destination.*', DB::raw("ticket_print.tp_actual_number||';'||EXTRACT(DAY FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(MONTH FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(YEAR FROM  tp_date::TIMESTAMP)||';'||ticket_print.tp_content||';'||ticket_print.tp_rate AS newticket,EXTRACT(DAY FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(MONTH FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(YEAR FROM  tp_date::TIMESTAMP) AS bookdate"))->get();
	$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>DTPC</title>
                        <style type="text/css">
                            body {
                                font-family: Arial, Helvetica, sans-serif;
                                margin: 0px;
                                padding: 0px;
                            }
                            p,h1,h2,h3,h4,h5,h6{
                                margin: 0px;
                                padding: 0px;
                            }
                            hr {
                                color: #CCC;
                            }
                            </style>
                        </head>
                    <body>';
	$i = 0;
	foreach ($summaryDetails as $data) {
		$qrData = $data->newticket;
		$classdata = \DB::table('ticket_class')
			->leftjoin('class', 'class.class_id', '=', 'ticket_class.tc_class_id')
			->where('tc_tp_id', '=', $data->tp_id)
			->orderBy('tc_id')
			->select('ticket_class.*', 'class.*')->get();
		//$email = $data->pusr_email;
		//$name = $data->pusr_name;

		$body .= '<table style="font-family: Arial, Helvetica, sans-serif; font-size: 16px;" width="100%" cellspacing="0" cellpadding="3" border="0">
  <tbody><tr>
    <td align="center"><strong>' . APPTITLE . '</strong></td>
  </tr>
  <tr>
    <td style="font-size: 10px;" align="center">' . $data->dest_name . ', ' . $data->dest_place . ' - ' . $data->dest_pincode . '<br> Ph: ' . $data->dest_phone . '</td>
  </tr>
  <tr>
    <td>
    <table style="border-collapse: collapse; border:solid 2px #000000; padding-top:8px; padding-bottom:8px; font-size: 14px; font-weight: bold" width="100%" cellspacing="0" cellpadding="3" border="1">
        <tbody>
          <tr style="font-size: 12px;">
            <th style=" border-bottom: solid 2px;" align="left">Ticket</th>
            <th style=" border-bottom: solid 2px;" align="left">Qty</th>
            <th style=" border-bottom: solid 2px;" align="left">Price</th>
          </tr>';
		foreach ($classdata as $classvalue) {
			$body .= '<tr>
            <td>' . $classvalue->class_name . '</td>
            <td>' . $classvalue->tc_number . '</td>
            <td>' . $classvalue->total_rate . '</td>
          </tr> ';
		}
		$body .= '</tbody>
    </table></td>
  </tr>
  <tr>
    <td style="padding-top: 10px;" valign="middle" align="center"><strong>Price ----- Rs. ' . $data->tp_rate . '</strong></td>
  </tr>
  <tr>
    <td align="center"><hr color="#000000"></td>
  </tr>
  <tr>
    <td style="font-size:12px;" align="center"><table width="100%" cellspacing="0" cellpadding="3" border="0">
      <tbody>
         <tr><td width="20%" valign="top"><img src="http://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . $qrData . '">'; //.//QrCode::size(120)->generate($data->tp_actual_number.';'.$data->tp_date).'
		$body .= '</td>
          <td width="80%" valign="top"><table width="100%" cellspacing="0" cellpadding="3" border="0">
            <tbody>
              <tr>
                <td style="font-size:11px;"><strong>' . $data->tp_actual_number . ' | ' . $data->bookdate . ' ' . $data->tp_time . '</strong></td>
              </tr>
              <tr>
                <td style="font-size:10px;">*This ticket is not retainable or refundable<br>
**Keep this ticket till you leave the destination</td>
              </tr>';
		if ($data->dest_display_terms_ticket == OT_YES && $data->dest_terms != '') {

			$body .= '<tr >
                <td style="font-size:10px;"><ul style="list-style: none;line-height: 1em;list-style-position: outside;padding-left: -10px;" *ngFor="let term of terms;let i=index;">';
			$terms = json_decode($data->dest_terms);
			foreach ($terms as $term) {
				$body .= '<li>' . $term . '</li>';
			}

			$body .= '</ul></td>
              </tr>';
		}

		$body .= '<tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td style="font-size:11px;">' . $data->dest_website . '</td>
              </tr>
            </tbody>
          </table></td>
        </tr>
      </tbody>
    </table></td>
  </tr>
</tbody></table>
<div style="page-break-after: always"></div>
  <table style="font-family: Arial, Helvetica, sans-serif; font-size: 16px;" width="100%" cellspacing="0" cellpadding="3" border="0">
  <tbody>
    <tr>
      <td style="font-size:12px;" align="center"><strong>' . $data->dest_name . ' - ' . $data->dest_place . '</strong></td>
    </tr>
    <tr>
      <td style="font-size:12px;" align="center">Ticket No. ' . $data->tp_actual_number . ' | ' . $data->bookdate . ' ' . $data->tp_time . '</td>
    </tr>
    <tr>
      <td>
      <table style="border-collapse: collapse; border:solid 2px #000000; padding-top:8px; padding-bottom:8px; font-size: 14px; font-weight: bold" width="100%" cellspacing="0" cellpadding="3" border="1">
        <tbody>
          <tr style="font-size: 12px;">
            <th style=" border-bottom: solid 2px;" align="left">Ticket</th>
            <th style=" border-bottom: solid 2px;" align="left">Qty</th>
            <th style=" border-bottom: solid 2px;" align="left">Price</th>
          </tr>';
		foreach ($classdata as $classvalue) {
			$body .= '<tr>
            <td>' . $classvalue->class_name . '</td>
            <td>' . $classvalue->tc_number . '</td>
            <td>' . $classvalue->total_rate . '</td>
          </tr> ';
		}
		$body .= '</tbody>
      </table></td>
    </tr>
    <tr>
      <td valign="middle" align="center"><strong>Price ------- Rs.' . $data->tp_rate . '</strong></td>
    </tr>
  </tbody>
</table>';

		if (count($summaryDetails) != ($i - 1) && count($summaryDetails) > 1) {
			$body .= '<div style="page-break-before: always;"></div>';
		}

		$i++;
	}

	$body .= '</body>
    </html>';
	$basePath = __DIR__;
	$pathToVendor = substr($basePath, 0, -3) . 'vendor/autoload.php';
	require_once $pathToVendor;
	$mpdf = new \Mpdf\Mpdf();
	$mpdf->WriteHTML($body);
	//$fileName = Crypt::encrypt($id);
	$rand = rand(500, 25647) . $id;
	$fileName = storage_path("") . '/' . $rand;
	$mpdf->Output($fileName . '.pdf', 'F');
	$content = "Hi " . $name . ", Your booking has been successfully completed. Please check the attachment of tickets for visitng. Thank You";
	/**Insert into mail queue */
	$dbArray['subject'] = "DTPC Ticket Booking Summary..!";
	$dbArray['message'] = $content;
	$dbArray['smq_recipient'] = $email;
	DB::table('sms_mail_que')->insert($dbArray);
	$emailId = DB::getPdo()->lastInsertId();
	//Mail file
	$dbArray = array();
	$dbArray['mf_smq_id'] = $emailId;
	$dbArray['mf_file'] = $fileName . '.pdf';
	DB::table('mail_file')->insert($dbArray);
	return $rand;
}

/**
 * Generate pdf Report
 */
if (!function_exists('initialtransaction')) {
	function initialtransaction($paytmParams) {
		$PAYTM_MERCHANT_KEY_WASTE = PAYTM_MURCHANT_KEY; //'D%#NTjhk0PVsoo91';//'kWDhLTxGEzPjrklK';
		$PAYTM_MERCHANT_MID_WASTE = PAYTM_MID;'Cochin79132462319127'; //'KALAMA86133868928245';
		include base_path() . '/app/Library/PaytmChecksum.php';
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $PAYTM_MERCHANT_KEY_WASTE);
		$paytmParams["head"] = array(
			"signature" => $checksum,
		);

		$paymentId = $paytmParams['body']['orderId'];

		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/* for Staging
			    $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=" . $PAYTM_MERCHANT_MID_WASTE . "&orderId=" . $paymentId;
		*/

		/* for Production */
		$url = PAYTM_URL . $PAYTM_MERCHANT_MID_WASTE . "&orderId=" . $paymentId;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$response = curl_exec($ch);
		$data['response'] = $response;
		$data['request'] = $post_data;
		return $data;
	}
	/**
	 * @author    Sabin P V
	 * @copyright Origami Technologies
	 * @created    29/12/2021
	 * @license http://www.origamitechnologies.com
	 */

	function checktransactionstatus($bookingTransactionId) {

		// $PAYTM_MERCHANT_KEY_WASTE = 'D%#NTjhk0PVsoo91';//'kWDhLTxGEzPjrklK';
		// $PAYTM_MERCHANT_MID_WASTE = 'Cochin79132462319127';//'KALAMA86133868928245';
		$PAYTM_MERCHANT_KEY_WASTE = PAYTM_MURCHANT_KEY; //'D%#NTjhk0PVsoo91';//'kWDhLTxGEzPjrklK';
		$PAYTM_MERCHANT_MID_WASTE = PAYTM_MID; //'Cochin79132462319127'; //'KALAMA86133868928245';
		include base_path() . '/app/Library/PaytmChecksum.php';

		$paytmParams = array();

		$paytmParams["body"] = array(
			"mid" => $PAYTM_MERCHANT_MID_WASTE,
			"orderId" => $bookingTransactionId,
		);

		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $PAYTM_MERCHANT_KEY_WASTE);

		$paytmParams["head"] = array(
			"signature" => $checksum,
		);

		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/* for Staging */
		$url = "https://securegw-stage.paytm.in/v3/order/status";

		/* for Production */
		//$url = PAYTM_TXN_STATUS_URL_WASTE;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$response = curl_exec($ch);
		return $response;
	}
	function analyzePaytmResponse($responseInfo) {
		$response = json_decode($responseInfo, true);
		extract($response);
		$gatewayStatus = $body['resultInfo']['resultStatus'];
		$transactionId = $body['orderId'];

		// $amount = $body['txnAmount'];
		// if ($gatewayStatus == 'TXN_SUCCESS') {
		//   $status = PAYMENT_SUCCESS;
		//   $order_status = PAYMENT_SUCESS_STATUS_TEXT;

		// } else if ($gatewayStatus == 'TXN_FAILURE') {
		//   $status = PAYMENT_FAILED;
		//   $order_status = PAYMENT_FAILED_STATUS_TEXT;
		//   $error = OT_YES;
		// } else {
		//   $status = PAYMENT_PENDING;
		//   $order_status = PAYMENT_PENDING_STATUS_TEXT;
		//   $error = OT_YES;
		// }

		// $payment_string = json_encode($response);

		// $paymentUpdater = new Manage_Model_Payment();
		// $paymentUpdater->payment_tran_id = $transactionId;
		// $paymentUpdater->payment_order_status = $order_status;
		// $paymentUpdater->payment_status = $status;
		// $paymentUpdater->payment_hash_value = $head['signature'];
		// $paymentUpdater->payment_responce = $payment_string;

		// $paymentAttemptFetcher = new Manage_Model_Paymentattempts();
		// $paymentAttemptFetcher->pa_id = $transactionId;
		// $paymentAttemptDetails = $paymentAttemptFetcher->getPaymentbyRegistration();

		// $paymentInserter = new Manage_Model_Payment();
		// $regId = $paymentAttemptDetails->pa_wsr_id;
		// $transdate = $paymentAttemptDetails->pa_transaction_date;

		// $paymentInserter->payment_wsr_id = $regId;
		// $paymentInserter->payment_tran_id = $transactionId;
		// $paymentAlreadyInserted = $paymentInserter->checkIfPaymentInserted();

		// if (!$paymentAlreadyInserted) {
		//   $paymentInserter->payment_wsr_id = $regId;
		//   $paymentInserter->payment_amount = $body['txnAmount'];
		//   $paymentInserter->payment_order_status = $order_status;
		//   $paymentInserter->payment_status = $status;
		//   $paymentInserter->payment_transaction_date = $transdate;
		//   $paymentInserter->payment_hash_value = $head['signature'];
		//   $paymentInserter->payment_responce = $payment_string;
		//   $paymentId = $paymentInserter->createPayment();
		// } else {
		//   $paymentUpdater->updateOfflinePaytmresponce();
		// }

		// if ($gatewayStatus == 'TXN_SUCCESS') {
		//   $routeCountFetcher = new Waste_Model_Tariff();
		//   $routeCountFetcher->pay_opt_id = ONLINEPAYMENT;
		//   $seqMax = $routeCountFetcher->getmaxseq();
		//   $sequenceNo = $seqMax[0]['recieot'];
		//   $recieptNo = ($sequenceNo == NULL) ? 0 : $sequenceNo;

		//   $payArray['pay_amount_total'][0] = $amount;
		//   $payArray['pay_remarks'][0] = "Online Payment by User";
		//   $payArray['pay_wsr_id'][0] = (int) $regId;
		//   $payArray['pay_app_user_id'][0] = (int) $regId;
		//   $payArray['pay_date'][0] = date('Y-m-d');
		//   $payArray['pay_mode'][0] = 2;
		//   $payArray['pay_reciept_no'][0] = (int) $recieptNo + 1;
		//   $payArray['pay_opt_id'][0] = ONLINEPAYMENT;

		//   $billInserter = new Waste_Model_Billpayment();
		//   $insertStatus = $billInserter->doInsert($payArray);
		// }

		// if ($gatewayStatus == PAYMENT_FAILED_STATUS_TEXT_PAYTM) {
		//   OT_Feedback::seterror("failed_transaction_paytm");
		// } else {
		//   return false;
		// }
	}
    /**
     * Generate pdf Report
     */
    function movefile($file) {
        $original_filename = $file->getClientOriginalName();
        $original_filename_arr = explode('.', $original_filename);
        $file_ext = end($original_filename_arr);
        $path = 'uploads/files/';
        $destinationPath = public_path($path); // upload path
        File::makeDirectory($destinationPath, 0777, true, true);
        $image = uniqid().time().'.'.$file_ext;         
        if ($file->move($destinationPath, $image) ) {

        }
        else
            return 0;
    }
}
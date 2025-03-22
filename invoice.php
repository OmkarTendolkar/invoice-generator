<?php
  require 'config/database.php';
  require 'vendor/autoload.php';

  // Function to get user input from terminal
  function getInput($prompt){
    echo $prompt . ': ';
    return trim(fgets(STDIN));
  }

  $invoice_date = date("d F Y");
  $client_name = getInput("Enter Client Name");
  $client_email = getInput("Enter Client Email");
  $client_address = getInput("Enter Client Address");
  $client_phone = getInput('Enter Client Phone Number');

  $s_no = 0;
  $product_details = [];
  $total_quantity = 0;
  $total_tax = 0;
  $total_amount = 0;

  while (true){
        $product_name = getInput("Enter Product Name");
        $unit_price = getInput("Enter Unit Price");
        $quantity = getInput('Enter number of ' . $product_name . ' quantity');
        $c_gst = getInput('Enter CGST Percentage Rate');
        $s_gst = getInput('Enter SGST Percentage Rate');

        // Calculations
        $tax = $c_gst + $s_gst;
        $net_amt = $unit_price * $quantity;
        $tax_amount = ($net_amt * $tax) / 100;
        $amount = $net_amt + $tax_amount;
        $total_quantity += $quantity;
        $total_tax += $tax_amount;
        $total_amount = $total_amount + $amount;

        $product_details[] = [
            'id' => ++$s_no,
            'name' => $product_name,
            'unit_price' => $unit_price,
            'quantity' => $quantity,
            'net_amt' => $net_amt,
            'cgst_rate' => $c_gst,
            'sgst_rate' => $s_gst,
            'tax' => $tax,
            'tax_amount' => $tax_amount,
            'amount' => $amount
        ];

        $add_item = getInput('Do you add more product (y/N)? ');
        if($add_item  == 'N'){
            break;
        }
    };
    

    $product_details_json = json_encode($product_details);


    $extra_charges = getInput('Do you want to add extra charges ? ');
    $extra_charges_tax = getInput('How much tax rate need to include in extra charges?');

    $extra_charges_amt = $extra_charges + (($extra_charges * $extra_charges_tax) / 100);

    $discount = getInput("Enter Discount Percentage ");
    $discount_amount  = (($total_amount + $extra_charges_amt)  * $discount) / 100;

    $final_amount = $total_amount + $extra_charges_amt - $discount_amount;

    $received_amt = getInput('How much amount you recieved in &#8377;?');
    $balance_amt = $final_amount - $received_amt;
    $prev_balance = getInput('How much previous balance left in &#8377;?');
    $curr_balance = $balance_amt + $prev_balance;

  // Save to Database
  $sql = "INSERT INTO invoices (client_name, client_email, bill_address, product_details, total_amount, discount, final_amount)
          VALUES ('$client_name', '$client_email', '$client_address', '$product_details_json', '$total_amount', '$discount', '$final_amount')";
  
  $conn->query($sql);
  $invoice_id = $conn->insert_id;     // Stored inserted record ID

  // Create PDF Invoice
  $pdf = new \Mpdf\Mpdf([
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
  ]);

  $pdf->AddPage();
  $pdf->SetFont('helvetica', '', 12);

  $css = file_get_contents(__DIR__ . '/css/style.css');

  $pdf->WriteHTML($css, 1);

  // Invoice Content
  $html = <<<EOD
          <div class="bg-white p-4">
              <div class="container border border-secondary p-4">
                  <table class="table table-bordered mb-2">
                      <tbody>
                          <tr>
                              <td style="width: 50%; vertical-align: top;">
                                  <h2 class="font-weight-bold">Omkar Tendolkar</h2>
                                  <div class="fs-7">11, Main Market, Chandni Chowk, New Delhi, Delhi 110006</div>
                                  <div class="fs-7"> 
                                      <span class="font-weight-bold">Phone: </span> +91 1234567895
                                  </div>
                                  <div class="fs-7"> 
                                      <span class="font-weight-bold">GSTIN: </span>00XXXXX0000X0XX
                                  </div>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">PAN Number: </span>XXXXX0000X
                                  </div>
                              </td>
                              <td style="width: 50%; vertical-align: top;">
                                  <h2 class="h5 font-weight-bold">TAX INVOICE</h2>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">Invoice No: </span>#$invoice_id
                                  </div>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">Invoice Date: </span>$invoice_date</div>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">Email Id: </span> $client_email
                                  </div>
                              </td>
                          </tr>
                          <tr>
                              <td style="width: 50%; vertical-align: top;">
                                  <h3 class="h6 font-weight-bold">BILL TO</h3>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">$client_name</span>
                                  </div>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">Address: </span>$client_address
                                  </div>
                                  <div class="fs-7" >
                                      <span class="font-weight-bold">Phone: </span>$client_phone
                                      &nbsp;&nbsp;
                                      <span class="font-weight-bold">PAN Number: </span>(Need to be removed)
                                  </div>
                                  <div class="fs-7">
                                      <span class="font-weight-bold">GSTIN: </span>08HULMP2839A1AB
                                      &nbsp;&nbsp;
                                      <span class="font-weight-bold">Place of Supply: </span>Delhi
                                  </div>
                              </td>
                              <td style="width: 50%; vertical-align: top;">
                                  <h3 class="h6 font-weight-bold">SHIP TO</h3>
                                  <div class="fs-7"><span class="font-weight-bold">$client_name</span></div>
                                  <div class="fs-7">$client_address</div>
                              </td>
                          </tr>
                      </tbody>
                  </table>
                  <table class="table table-bordered mb-2 fs-7">
                      <thead>
                          <tr>
                              <th>S. No.</th>
                              <th>Description</th>
                              <th>Quantity</th>
                              <th>Unit Price</th>
                              <th>Net Amount</th>
                              <th>Tax</th>
                              <th>Amount</th>
                          </tr>
                      </thead>
                      <tbody>
        EOD;
        foreach ($product_details as $product) {
            $html .= <<<EOD
                        <tr>
                            <td>{$product['id']}</td>
                            <td>{$product['name']}</td>
                            <td>{$product['quantity']}</td>
                            <td>&#8377; {$product['unit_price']}</td>
                            <td>&#8377; {$product['net_amt']}</td>
                            <td>&#8377; {$product['tax_amount']} ({$product['tax']}&#37;)</td>
                            <td>&#8377; {$product['amount']}</td>
                        </tr>
            EOD;
        }
        $extra_charges_tax_amt = $extra_charges_amt - $extra_charges;
        $total_tax += $extra_charges_tax_amt;
        $html .= <<<EOD
                          <tr>
                              <td></td>
                              <td class="text-right">Extra Charges</td>
                              <td></td>
                              <td>&#8377; $extra_charges</td>
                              <td></td>
                              <td>&#8377; $extra_charges_tax_amt ($extra_charges_tax&#37;)</td>
                              <td>&#8377; $extra_charges_amt</td>
                          </tr>
                          <tr>
                              <td></td>
                              <td class="text-right">Discount ($discount&#37;)</td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td>&#8722; &#8377; $discount_amount</td>
                          </tr>
                          <tr>
                              <td></td>
                              <td class="font-weight-bold">TOTAL</td>
                              <td class="font-weight-bold">$total_quantity</td>
                              <td></td>
                              <td></td>
                              <td class="font-weight-bold">&#8377; $total_tax</td>
                              <td class="font-weight-bold">&#8377; $final_amount</td>
                          </tr>
                      </tbody>
                  </table>
                  <table class="table table-bordered mb-2 font-weight-bold fs-7">
                      <tbody>
                          <tr>
                              <td>
                                <div>Received Amount</div>
                                <div>&#8377; $received_amt</div>
                              </td>
                              <td>
                                <div>Balance Amount</div>
                                <div>&#8377; $balance_amt</div>
                              </td>
                              <td>
                                <div>Previous Balance</div>
                                <div>&#8377; $prev_balance</div>
                              </td>
                              <td>
                                <div>Current Balance</div>
                                <div>&#8377; $curr_balance</div>  
                              </td>
                          </tr>
                      </tbody>
                  </table>
                  <table class="table table-bordered mb-2 fs-7">
                      <thead>
                          <tr>
                              <th rowspan="2">S. No.</th>
                              <th rowspan="2">Taxable Amount</th>
                              <th colspan="2">CGST</th>
                              <th colspan="2">SGST</th>
                              <th rowspan="2">Total Tax Amount</th>
                          </tr>
                          <tr>
                              <th>Rate</th>
                              <th>Amount</th>
                              <th>Rate</th>
                              <th>Amount</th>
                          </tr>
                      </thead>
                      <tbody>
        EOD;
        foreach ($product_details as $product) {
            $cgst_amt = ($product['net_amt'] * $product['cgst_rate']) / 100;
            $sgst_amt = ($product['net_amt'] * $product['sgst_rate']) / 100;
            $html .= <<<EOD
                        <tr>
                            <td>{$product['id']}</td>
                            <td>&#8377; {$product['net_amt']}</td>
                            <td>{$product['cgst_rate']}%</td>
                            <td>&#8377; {$cgst_amt}</td>
                            <td>{$product['sgst_rate']}%</td>
                            <td>&#8377; {$sgst_amt}</td>
                            <td>&#8377; {$product['tax_amount']}</td>
                        </tr>
            EOD;
        }
        $html .= <<<EOD
                          <tr>
                              <td></td>
                              <td>&#8377; $extra_charges</td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td>&#8377; $extra_charges_amt ($extra_charges_tax&#37;)</td>
                          </tr>
                          <tr>
                              <td></td>
                              <td class="font-weight-bold">TOTAL</td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td class="font-weight-bold">&#8377; $total_tax</td>
                          </tr>
                      </tbody>
                  </table>
                  <table class="table table-bordered mb-4 fs-7">
                      <tbody>
                          <tr>
                              <td colspan="3">
                                  <span class="font-weight-bold">Remark : </span>
                                  <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ab odit, sapiente dolore, voluptate, ullam consectetur dignissimos alias ut qui suscipit dicta? Accusantium, modi similique quidem deserunt maxime nesciunt asperiores consectetur.</span>
                              </td>
                          </tr>
                          <tr>
                              <td rowspan="2" class="col-md-6" style="width: 37%; vertical-align: top">
                                  <p class="font-weight-bold">Terms & Conditions</p>
                                  <ol>
                                      <li>Customer will pay the GST</li>
                                      <li>Customer will pay the Delivery charges</li>
                                      <li>Pay due amount within 15 days</li>
                                  </ol>
                              </td>
                              <td class="col-md-4" style="width: 37%;">
                                  <p class="font-weight-bold">Bank Details</p>
                                  <p>Account holder: </p>
                                  <p>Account number: </p>
                                  <p>Bank Name: </p>
                                  <p>IFSC code: </p>
                                  <p>UPI ID: </p>
                              </td>
                              <td class="col-md-3 text-center align-middle" style="height: 100px; width: 16%;">
                                  <div class="d-flex align-items-center justify-content-center h-100">
                                      <img src="." alt="Authorized Signatory Placeholder" class="img-fluid">
                                      <span>Scan QR Code</span>
                                  </div>
                              </td>
                              
                          </tr>
                          <tr>
                              <td colspan="2" class="text-right align-bottom" style="height: 120px;">
                                  <div>Authorised Signatory For</div>
                                  <div>Akash Enterprises</div>
                              </td>
                          </tr>
                          
                      </tbody>
                  </table>
              </div>
          </div>
    EOD;

  // $pdf->WriteHTML($html, true, false, true, false, '');
  $pdf->WriteHTML($html);

  // Ensure directory exists
  $folder = __DIR__ . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . date('Y/M');

  if(!is_dir($folder)){
    mkdir($folder, 0777, true);
  }

  $filename = "INV_" . date('Y-M-d') . "_$invoice_id.pdf";

  // Save PDF
  $full_path = $folder . DIRECTORY_SEPARATOR . $filename;

  $pdf->Output($full_path, 'F');

  $pdf_path = "invoices/" . date('Y/M') . "/" . $filename;

  // Update DB with PDF path 
  $conn->query("UPDATE invoices SET pdf_path='$pdf_path' WHERE id = $invoice_id");

  // Success Message
  echo "✅ Invoice Generated & Saved in Database!\n"
?>
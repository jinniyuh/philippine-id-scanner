<?php
session_start();
include 'includes/conn.php';

// ✅ Admin session check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ✅ Month filter (optional)
$month = isset($_GET['month']) ? date('m', strtotime($_GET['month']."-01")) : date('m');
$year  = date('Y');

function getMonthlyCounts($conn, $table, $date_col, $where = '') {
    $sql = "SELECT MONTH($date_col) AS month, COUNT(*) AS cnt 
            FROM $table 
            WHERE YEAR($date_col) = YEAR(CURDATE()) $where
            GROUP BY MONTH($date_col) 
            ORDER BY MONTH($date_col)";
    $res = $conn->query($sql);
    $data = array_fill(1, 12, 0);
    while ($row = $res->fetch_assoc()) {
        $data[(int)$row['month']] = (int)$row['cnt'];
    }
    return array_values($data);
}

$pharma_data     = getMonthlyCounts($conn, "transactions", "transaction_date", "AND type='pharma'");
$livestock_data  = getMonthlyCounts($conn, "transactions", "transaction_date", "AND type='livestock'");
$transaction_data= getMonthlyCounts($conn, "transactions", "transaction_date");

function linearRegression(array $y) {
    $n = count($y);
    if ($n < 2) {
        return ['slope'=>0,'intercept'=>$n?floatval($y[0]):0,'r2'=>0,'predicted'=>$y];
    }
    $x_mean = ($n+1)/2.0;
    $y_mean = array_sum($y)/$n;
    $num=0;$den=0;
    for ($i=0;$i<$n;$i++) {
        $xi=$i+1; $yi=floatval($y[$i]);
        $num += ($xi-$x_mean)*($yi-$y_mean);
        $den += pow($xi-$x_mean,2);
    }
    $slope=$den?($num/$den):0;
    $intercept=$y_mean-$slope*$x_mean;
    $predicted=[];
    for($i=0;$i<$n;$i++){ $xi=$i+1; $predicted[]=$slope*$xi+$intercept; }
    $ss_tot=0;$ss_res=0;
    foreach($y as $i=>$yi){$yi=floatval($yi);$ss_tot+=pow($yi-$y_mean,2);$ss_res+=pow($yi-$predicted[$i],2);}
    $r2=$ss_tot?max(0,1-$ss_res/$ss_tot):0;
    return ['slope'=>$slope,'intercept'=>$intercept,'r2'=>$r2,'predicted'=>$predicted];
}

function forecastLinear(array $data,$periods=3){
    $n=count($data);
    if($n==0) return array_fill(0,$periods,0);
    if($n==1) return array_fill(0,$periods,(int)$data[0]);
    $reg=linearRegression($data);
    $slope=$reg['slope']; $intercept=$reg['intercept'];
    $forecast=[];
    for($i=1;$i<=$periods;$i++){
        $x=$n+$i;
        $yhat=$slope*$x+$intercept;
        $forecast[]=max(0,(int)round($yhat));
    }
    return $forecast;
}

function calculateTrendLinear(array $historical,array $forecast){
    $nHist=count($historical);
    if($nHist<2) return ['trend'=>'stable','emoji'=>'➖','text'=>'Insufficient data','change'=>0,'r2'=>0];
    $window=min(3,$nHist);
    $recent=array_slice($historical,-$window);
    $current_avg=array_sum($recent)/$window;
    $forecast_avg=count($forecast)?array_sum($forecast)/count($forecast):0;
    $change=$current_avg>0?(($forecast_avg-$current_avg)/$current_avg)*100:0;
    $change=round($change,1);
    if(abs($change)<5){$trend='stable';$emoji='➖';$text='Stable trend expected';}
    elseif($change>0){$trend='increasing';$emoji='📈';$text="Increasing trend (+$change%)";}
    else{$trend='decreasing';$emoji='📉';$text="Decreasing trend ($change%)";}
    $r2=round(linearRegression($historical)['r2']*100,1);
    return ['trend'=>$trend,'emoji'=>$emoji,'text'=>$text,'change'=>$change,'r2'=>$r2];
}

$pharma_forecast      = forecastLinear($pharma_data,3);
$livestock_forecast   = forecastLinear($livestock_data,3);
$transaction_forecast = forecastLinear($transaction_data,3);

$pharma_trend      = calculateTrendLinear($pharma_data,$pharma_forecast);
$livestock_trend   = calculateTrendLinear($livestock_data,$livestock_forecast);
$transaction_trend = calculateTrendLinear($transaction_data,$transaction_forecast);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Reports - Forecast</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-4">
  <h3>📊 Forecast Reports</h3>

  <div class="row mt-4">
    <!-- Pharma -->
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h5>Pharmaceuticals Dispensed</h5>
        <canvas id="pharmaChart" height="200"></canvas>
        <p class="mt-2"><?=$pharma_trend['emoji']?> <?=$pharma_trend['text']?> | R² <?=$pharma_trend['r2']?>%</p>
      </div>
    </div>
    <!-- Livestock -->
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h5>Livestock Distributed</h5>
        <canvas id="livestockChart" height="200"></canvas>
        <p class="mt-2"><?=$livestock_trend['emoji']?> <?=$livestock_trend['text']?> | R² <?=$livestock_trend['r2']?>%</p>
      </div>
    </div>
    <!-- Transactions -->
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h5>Total Transactions</h5>
        <canvas id="transactionChart" height="200"></canvas>
        <p class="mt-2"><?=$transaction_trend['emoji']?> <?=$transaction_trend['text']?> | R² <?=$transaction_trend['r2']?>%</p>
      </div>
    </div>
  </div>

<script>
const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
const pharmaData      = <?=json_encode($pharma_data)?>;
const pharmaForecast  = <?=json_encode($pharma_forecast)?>;
const livestockData   = <?=json_encode($livestock_data)?>;
const livestockForecast=<?=json_encode($livestock_forecast)?>;
const transactionData = <?=json_encode($transaction_data)?>;
const transactionForecast=<?=json_encode($transaction_forecast)?>;

function makeChart(ctx,label,actual,forecast){
  return new Chart(ctx,{
    type:'line',
    data:{
      labels: months.slice(0,actual.length+forecast.length),
      datasets:[
        {label:label,data:actual,borderColor:'blue',fill:false},
        {label:'Forecast',data:Array(actual.length).fill(null).concat(forecast),borderColor:'orange',borderDash:[5,5],fill:false}
      ]
    },
    options:{responsive:true,scales:{y:{beginAtZero:true}}}
  });
}
makeChart(document.getElementById('pharmaChart'),"Pharma",pharmaData,pharmaForecast);
makeChart(document.getElementById('livestockChart'),"Livestock",livestockData,livestockForecast);
makeChart(document.getElementById('transactionChart'),"Transactions",transactionData,transactionForecast);
</script>
</body>
</html>

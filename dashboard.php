<?php
// dashboard.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));

// 1. FETCH DASHBOARD STATS
$stats = [
    'total'     => $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn() ?: 0,
    'available' => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Available'")->fetchColumn() ?: 0,
    'adopted'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Adopted'")->fetchColumn() ?: 0,
    'pending'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Pending'")->fetchColumn() ?: 0,
    'medical'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Medical Care'")->fetchColumn() ?: 0,
];

// 2. FETCH SPECIES BREAKDOWN
$species_stmt = $pdo->query("SELECT species, COUNT(*) as count FROM animals GROUP BY species");
$species_data = $species_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$dogs    = $species_data['Dog']  ?? 0;
$cats    = $species_data['Cat']  ?? 0;
$other   = array_sum($species_data) - $dogs - $cats;
$total_sp = max($stats['total'], 1);

// 3. FETCH GENDER BREAKDOWN
$male_count   = $pdo->query("SELECT COUNT(*) FROM animals WHERE gender='Male'")->fetchColumn() ?: 0;
$female_count = $pdo->query("SELECT COUNT(*) FROM animals WHERE gender='Female'")->fetchColumn() ?: 0;

// 4. FETCH RECENT ANIMALS (Last 5)
$recent_animals = $pdo->query("SELECT name, species, breed, age, status FROM animals ORDER BY animal_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    switch ($status) {
        case 'Available':   return '<span class="bdg bg">Available</span>';
        case 'Adopted':     return '<span class="bdg bb">Adopted</span>';
        case 'Pending':     return '<span class="bdg by">Pending</span>';
        case 'Medical Care':return '<span class="bdg bpu">Medical Care</span>';
        default:            return '<span class="bdg bgray">'.htmlspecialchars($status).'</span>';
    }
}

// 5. FETCH UPCOMING VACCINATIONS
$upcoming_vaccines = [];
try {
    $upcoming_vaccines = $pdo->query("SELECT an.name as animal_name, vt.vaccine_name, av.nextDate
                                      FROM animal_vaccination av
                                      JOIN animals an ON av.animal_id = an.animal_id
                                      JOIN vaccination_types vt ON av.vtype_id = vt.vtype_id
                                      WHERE av.nextDate IS NOT NULL
                                      ORDER BY av.nextDate ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// 6. FETCH ACTIVITY FEED
$activity = [];
try {
    $activity = $pdo->query("SELECT u.fullname, a.actiontype, a.targettable, a.details, a.created_at
                             FROM user_activity_log a
                             JOIN users u ON a.uid = u.uid
                             ORDER BY a.created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

function getActIcon($type) {
    $type = strtolower($type);
    if (strpos($type,'add')!==false||strpos($type,'create')!==false) return ['icon'=>'fa-plus','color'=>'var(--g)','bg'=>'var(--gl)'];
    if (strpos($type,'edit')!==false||strpos($type,'update')!==false) return ['icon'=>'fa-pen','color'=>'var(--pu)','bg'=>'var(--pul)'];
    if (strpos($type,'delete')!==false) return ['icon'=>'fa-trash','color'=>'var(--r)','bg'=>'var(--rl)'];
    return ['icon'=>'fa-bolt','color'=>'var(--y)','bg'=>'var(--yl)'];
}

$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
/* Dashboard-specific styles */
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;}
.sc{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:.25s;cursor:default;position:relative;overflow:hidden;animation:fadeUp .5s ease both;}
.sc:nth-child(1){animation-delay:.05s}.sc:nth-child(2){animation-delay:.10s}.sc:nth-child(3){animation-delay:.15s}.sc:nth-child(4){animation-delay:.20s}.sc:nth-child(5){animation-delay:.25s}
.sc:hover{transform:translateY(-3px);box-shadow:var(--shadow);}
.sc::after{content:'';position:absolute;right:-16px;top:-16px;width:68px;height:68px;border-radius:50%;opacity:.08;}
.sc.or::after{background:var(--p);}.sc.gr::after{background:var(--g);}.sc.bl::after{background:var(--b);}.sc.yw::after{background:var(--y);}.sc.pu::after{background:var(--pu);}
.sic{width:46px;height:46px;min-width:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;}
.sic.or{background:var(--pl);color:var(--p);}.sic.gr{background:var(--gl);color:var(--g);}.sic.bl{background:var(--bl);color:var(--b);}.sic.yw{background:var(--yl);color:var(--y);}.sic.pu{background:var(--pul);color:var(--pu);}
.sv h3{font-size:1.7rem;font-weight:900;line-height:1;font-variant-numeric:tabular-nums;}
.sv p{color:var(--text2);font-size:.7rem;font-weight:700;margin-top:3px;letter-spacing:.3px;}
.row2{display:grid;grid-template-columns:2fr 1fr;gap:18px;}
.seg{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px;}
.seg-opt{padding:5px 16px;border-radius:7px;font-weight:700;font-size:.76rem;cursor:pointer;transition:.2s;color:var(--text2);}
.seg-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.chart-wrap{display:flex;align-items:flex-end;gap:6px;height:160px;padding-top:10px;}
.cc{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;min-width:0;}
.cb{width:100%;border-radius:5px 5px 0 0;min-height:3px;background:linear-gradient(180deg,var(--p),var(--pd));transition:height 1.2s cubic-bezier(.16,1,.3,1);position:relative;cursor:pointer;}
.cb:hover{filter:brightness(1.15);}
.cb::after{content:attr(data-val);position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:.62rem;font-weight:800;color:var(--p);opacity:0;transition:.2s;white-space:nowrap;}
.cb:hover::after{opacity:1;}
.cl{font-size:.6rem;font-weight:700;color:var(--text2);}
.sp-item{display:flex;align-items:center;gap:11px;margin-bottom:16px;}
.sp-emoji{font-size:1.4rem;}
.sp-info{flex:1;}
.sp-row{display:flex;justify-content:space-between;margin-bottom:5px;}
.sp-name{font-size:.8rem;font-weight:700;}
.sp-count{font-size:.8rem;font-weight:900;}
.pb{height:6px;border-radius:4px;background:var(--bg3);overflow:hidden;}
.pf{height:100%;border-radius:4px;transition:width 1.4s cubic-bezier(.16,1,.3,1);}
.row3{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.right-col{display:flex;flex-direction:column;gap:18px;}
.act-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;margin-bottom:6px;background:var(--bg3);transition:.15s;}
.act-item:hover{background:var(--bg4);}
.act-ico{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0;}
.act-text{flex:1;min-width:0;}
.act-title{font-weight:700;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.act-sub{font-size:.68rem;color:var(--text2);margin-top:2px;}
.vacc-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;margin-bottom:6px;border:1px solid var(--border);}
.vacc-icon{width:32px;height:32px;border-radius:9px;background:var(--yl);color:var(--y);display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0;}
.vacc-info{flex:1;}
.vacc-name{font-weight:700;font-size:.8rem;}
.vacc-due{font-size:.68rem;color:var(--r);font-weight:700;margin-top:2px;}
.vacc-due.ok{color:var(--text2);}
.np{position:fixed;top:72px;right:16px;width:300px;background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);z-index:500;display:none;animation:fadeUp .25s ease;}
.np.show{display:block;}
.np-h{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.np-h h4{font-weight:800;font-size:.88rem;}
.np-i{padding:11px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;cursor:pointer;transition:.15s;}
.np-i:last-child{border-bottom:none;}
.np-i:hover{background:var(--bg3);}
.np-ic{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
@media(max-width:1100px){.stats-row{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.row2,.row3{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'dashboard';
    $page_title_default = 'Dashboard';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="stats-row">
      <div class="sc or"><div class="sic or"><i class="fas fa-paw"></i></div><div class="sv"><h3><?= $stats['total'] ?></h3><p data-i18n="total_animals">Total Animals</p></div></div>
      <div class="sc gr"><div class="sic gr"><i class="fas fa-check-circle"></i></div><div class="sv"><h3><?= $stats['available'] ?></h3><p data-i18n="available">Available</p></div></div>
      <div class="sc bl"><div class="sic bl"><i class="fas fa-house-heart"></i></div><div class="sv"><h3><?= $stats['adopted'] ?></h3><p data-i18n="adopted">Adopted</p></div></div>
      <div class="sc yw"><div class="sic yw"><i class="fas fa-clock"></i></div><div class="sv"><h3><?= $stats['pending'] ?></h3><p data-i18n="pending">Pending</p></div></div>
      <div class="sc pu"><div class="sic pu"><i class="fas fa-stethoscope"></i></div><div class="sv"><h3><?= $stats['medical'] ?></h3><p data-i18n="medical_care">Medical Care</p></div></div>
    </div>

    <div class="row2">
      <div class="card">
        <div class="ch">
          <div class="ct"><i class="fas fa-chart-bar"></i> Intake Chart</div>
          <div class="seg" id="chartSeg">
            <div class="seg-opt on" onclick="switchChart('weekly',this)">Weekly</div>
            <div class="seg-opt" onclick="switchChart('monthly',this)">Monthly</div>
          </div>
        </div>
        <div class="chart-wrap" id="mainChart"></div>
      </div>

      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-fire"></i> By Species</div></div>
        <div style="margin-top:4px">
          <div class="sp-item"><span class="sp-emoji">🐕</span><div class="sp-info"><div class="sp-row"><span class="sp-name">Dogs</span><span class="sp-count"><?= $dogs ?></span></div><div class="pb"><div class="pf" style="width:<?= round(($dogs/$total_sp)*100) ?>%;background:var(--p)"></div></div></div></div>
          <div class="sp-item"><span class="sp-emoji">🐈</span><div class="sp-info"><div class="sp-row"><span class="sp-name">Cats</span><span class="sp-count"><?= $cats ?></span></div><div class="pb"><div class="pf" style="width:<?= round(($cats/$total_sp)*100) ?>%;background:var(--b)"></div></div></div></div>
          <div class="sp-item"><span class="sp-emoji">🐾</span><div class="sp-info"><div class="sp-row"><span class="sp-name">Other</span><span class="sp-count"><?= $other ?></span></div><div class="pb"><div class="pf" style="width:<?= round(($other/$total_sp)*100) ?>%;background:var(--pu)"></div></div></div></div>
        </div>
        <div style="border-top:1px solid var(--border);margin-top:10px;padding-top:14px">
          <div class="ct" style="margin-bottom:12px"><i class="fas fa-venus-mars" style="color:var(--pu)"></i> By Gender</div>
          <div style="display:flex;gap:10px">
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:12px;text-align:center"><div style="font-size:1.4rem;font-weight:900;color:var(--b)"><?= $male_count ?></div><div style="font-size:.68rem;font-weight:700;color:var(--text2);margin-top:2px">Male</div></div>
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:12px;text-align:center"><div style="font-size:1.4rem;font-weight:900;color:var(--p)"><?= $female_count ?></div><div style="font-size:.68rem;font-weight:700;color:var(--text2);margin-top:2px">Female</div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row3">
      <div class="card" style="animation-delay:.4s">
        <div class="ch"><div class="ct"><i class="fas fa-paw"></i> Recent Animals</div><a href="view_animals.php" class="btn btn-gh btn-sm">All →</a></div>
        <div class="tw">
          <table>
            <thead><tr><th>Name</th><th>Species</th><th>Breed</th><th>Age (yrs)</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (count($recent_animals) > 0): ?>
                <?php foreach ($recent_animals as $a): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                    <td><?= htmlspecialchars($a['species']) ?></td>
                    <td style="color:var(--text2)"><?= htmlspecialchars($a['breed'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($a['age'] ?: '?') ?></td>
                    <td><?= getStatusBadge($a['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text2)">No animals registered yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="right-col">
        <div class="card" style="animation-delay:.45s">
          <div class="ch"><div class="ct"><i class="fas fa-syringe" style="color:var(--y)"></i> Upcoming Vaccines</div></div>
          <?php if (count($upcoming_vaccines) > 0): ?>
            <?php foreach ($upcoming_vaccines as $v):
              $is_overdue = (strtotime($v['nextDate']) < time());
              $badge = $is_overdue ? '<span class="bdg br">Overdue</span>' : '<span class="bdg by">Soon</span>';
              $date_class = $is_overdue ? '' : 'ok';
            ?>
              <div class="vacc-item">
                <div class="vacc-icon"><i class="fas fa-syringe"></i></div>
                <div class="vacc-info">
                  <div class="vacc-name"><?= htmlspecialchars($v['animal_name'] . ' — ' . $v['vaccine_name']) ?></div>
                  <div class="vacc-due <?= $date_class ?>"><?= $is_overdue ? 'Was due ' : 'Due ' ?><?= htmlspecialchars($v['nextDate']) ?></div>
                </div>
                <?= $badge ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty" style="padding:15px"><i class="fas fa-check-circle" style="color:var(--g)"></i><p>All vaccinations are up to date!</p></div>
          <?php endif; ?>
        </div>

        <div class="card" style="animation-delay:.5s">
          <div class="ch"><div class="ct"><i class="fas fa-bolt" style="color:var(--y)"></i> Activity Feed</div></div>
          <?php if (count($activity) > 0): ?>
            <?php foreach ($activity as $act):
              $icons = getActIcon($act['actiontype']);
            ?>
              <div class="act-item">
                <div class="act-ico" style="background:<?= $icons['bg'] ?>;color:<?= $icons['color'] ?>"><i class="fas <?= $icons['icon'] ?>"></i></div>
                <div class="act-text">
                  <div class="act-title"><?= htmlspecialchars($act['details'] ?: $act['actiontype'].' on '.$act['targettable']) ?></div>
                  <div class="act-sub"><?= htmlspecialchars($act['fullname']) ?> · <?= date('M d, H:i', strtotime($act['created_at'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty" style="padding:15px"><p>No recent activity.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{total_animals:"Total Animals",available:"Available",adopted:"Adopted",pending:"Pending",medical_care:"Medical Care"},
  ku:{total_animals:"کۆی ئاژەڵەکان",available:"بەردەستە",adopted:"قبووڵکرا",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی"}
};

// Chart
const chartData={weekly:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],vals:[2,5,3,7,4,6,3]},monthly:{labels:['Sep','Oct','Nov','Dec','Jan','Feb'],vals:[8,12,7,15,10,9]}};
let chartPeriod='weekly';
function renderChart(){
  const d=chartData[chartPeriod];const max=Math.max(...d.vals,1);
  document.getElementById('mainChart').innerHTML=d.labels.map((l,i)=>`<div class="cc"><div class="cb" data-val="${d.vals[i]}" style="height:0"></div><div class="cl">${l}</div></div>`).join('');
  setTimeout(()=>{document.querySelectorAll('.cb').forEach((el,i)=>{el.style.height=((d.vals[i]/max)*145)+'px';});},50);
}
function switchChart(p,el){chartPeriod=p;document.querySelectorAll('#chartSeg .seg-opt').forEach(x=>x.classList.remove('on'));el.classList.add('on');renderChart();}
window.addEventListener('load',()=>{
  renderChart();
  document.querySelectorAll('.pf').forEach(el=>{const w=el.style.width;el.style.width='0';setTimeout(()=>el.style.width=w,100);});
});
</script>
<?php require_once 'includes/layout_js.php'; ?>
<script src="includes/realtime.js"></script>
<script>
/* ── Live Dashboard Stats ─────────────────────────────────────
   Polls api/dashboard.php every 12s and smoothly updates numbers
────────────────────────────────────────────────────────────── */
const statMap = {
  total:   document.querySelector('.sc.or .sv h3'),
  available:document.querySelector('.sc.gr .sv h3'),
  adopted:  document.querySelector('.sc.bl .sv h3'),
  pending:  document.querySelector('.sc.yw .sv h3'),
  medical:  document.querySelector('.sc.pu .sv h3'),
};

function animateNum(el, target) {
  if (!el) return;
  const start = parseInt(el.textContent) || 0;
  if (start === target) return;
  const diff = target - start;
  const step = diff / 18;
  let cur = start;
  const t = setInterval(() => {
    cur += step;
    el.textContent = Math.round(cur);
    if ((step > 0 && cur >= target) || (step < 0 && cur <= target)) {
      el.textContent = target;
      clearInterval(t);
    }
  }, 28);
}

async function refreshDashboard() {
  try {
    const res  = await fetch('api/dashboard.php');
    if (!res.ok) return;
    const data = await res.json();
    if (data.error) return;
    const s = data.stats;
    animateNum(statMap.total,    s.total);
    animateNum(statMap.available,s.available);
    animateNum(statMap.adopted,  s.adopted);
    animateNum(statMap.pending,  s.pending);
    animateNum(statMap.medical,  s.medical);
  } catch(e) {}
}

// Initial + polling
refreshDashboard();
startAutoRefresh(refreshDashboard, 12000);
</script>
</body>
</html>
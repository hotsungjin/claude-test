<?php
/**
 * 설성목장 추천 리워드 — 관리자 대시보드 뷰
 */
?>
<div class="page-header">
  <h3>추천 리워드 대시보드</h3>
  <small><?= date('Y년 m월 d일') ?> 기준</small>
</div>

<!-- 어뷰징 알림 -->
<?php if (!empty($abuseAlerts)): ?>
<div class="alert alert-danger">
  <strong>⚠ 어뷰징 의심 회원 (<?= count($abuseAlerts) ?>명)</strong>
  <ul style="margin:8px 0 0;">
    <?php foreach ($abuseAlerts as $a): ?>
    <li>회원번호 <?= gd_htmlspecialchars($a['ancestor_mem_no']) ?> — 24시간 내 <?= $a['cnt'] ?>건 추천</li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- 핵심 지표 카드 -->
<div class="row" style="margin-bottom:20px;">
  <?php
  $cards = [
    ['label' => '전체 추천 회원', 'value' => number_format($summary['total_members']) . '명'],
    ['label' => '이번달 신규', 'value' => number_format($summary['new_members_this_month']) . '명'],
    ['label' => '이번달 매출', 'value' => '₩' . number_format($summary['total_sales'])],
    ['label' => '이번달 리워드', 'value' => '₩' . number_format($summary['reward_this_month'])],
    ['label' => '지난달 리워드', 'value' => '₩' . number_format($summary['reward_last_month'])],
    ['label' => '리워드 비율', 'value' => $summary['reward_ratio'] . '%', 'sub' => '매출 대비'],
  ];
  foreach ($cards as $card): ?>
  <div class="col-md-2 col-sm-4">
    <div class="panel panel-default" style="border-radius:8px;">
      <div class="panel-body" style="text-align:center; padding:16px;">
        <div style="font-size:12px; color:#888; margin-bottom:4px;"><?= $card['label'] ?></div>
        <div style="font-size:20px; font-weight:700; color:#333;"><?= $card['value'] ?></div>
        <?php if (!empty($card['sub'])): ?>
        <div style="font-size:11px; color:#aaa;"><?= $card['sub'] ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <!-- 등급 분포 -->
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><h4 class="panel-title">등급 분포</h4></div>
      <div class="panel-body">
        <?php
        $gradeColors = ['씨앗'=>'#86efac','새싹'=>'#4ade80','나무'=>'#22c55e','숲'=>'#16a34a','목장'=>'#14532d'];
        $total = array_sum($gradeDistribution);
        foreach ($gradeDistribution as $grade => $cnt):
          $pct = $total > 0 ? round(($cnt / $total) * 100) : 0;
        ?>
        <div style="margin-bottom:10px;">
          <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
            <span><?= $grade ?></span>
            <span><?= number_format($cnt) ?>명 (<?= $pct ?>%)</span>
          </div>
          <div style="background:#f0f0f0; border-radius:4px; height:10px;">
            <div style="background:<?= $gradeColors[$grade] ?? '#ccc' ?>; width:<?= $pct ?>%; height:10px; border-radius:4px;"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($gradeDistribution)): ?>
        <p style="color:#aaa; text-align:center;">데이터 없음</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- 리워드 비교 -->
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><h4 class="panel-title">리워드 지급 비교</h4></div>
      <div class="panel-body">
        <?php
        $compareData = [
          '지난달' => $summary['reward_last_month'],
          '이번달' => $summary['reward_this_month'],
        ];
        $maxVal = max($compareData) ?: 1;
        foreach ($compareData as $label => $val):
          $pct = round(($val / $maxVal) * 100);
        ?>
        <div style="margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
            <span><?= $label ?></span>
            <span>₩<?= number_format($val) ?></span>
          </div>
          <div style="background:#f0f0f0; border-radius:4px; height:20px;">
            <div style="background:#4ade80; width:<?= $pct ?>%; height:20px; border-radius:4px;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- 상위 추천인 TOP 10 -->
<div class="panel panel-default">
  <div class="panel-heading"><h4 class="panel-title">상위 추천인 TOP 10</h4></div>
  <div class="panel-body" style="padding:0;">
    <table class="table table-hover" style="margin:0;">
      <thead>
        <tr style="background:#f9f9f9;">
          <th>회원번호</th>
          <th>추천 코드</th>
          <th>등급</th>
          <th>직접 추천</th>
          <th style="text-align:right;">누적 리워드</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($topReferrers)): ?>
        <?php foreach ($topReferrers as $r): ?>
        <tr>
          <td><?= gd_htmlspecialchars($r['mem_no']) ?></td>
          <td><code style="color:#16a34a; font-size:14px;"><?= gd_htmlspecialchars($r['referral_code']) ?></code></td>
          <td><span class="label label-success"><?= gd_htmlspecialchars($r['grade']) ?></span></td>
          <td><?= number_format($r['network_count']) ?>명</td>
          <td style="text-align:right; font-weight:600;">₩<?= number_format($r['total_reward']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="5" style="text-align:center; color:#aaa; padding:24px;">데이터 없음</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 최근 리워드 내역 -->
<div class="panel panel-default">
  <div class="panel-heading"><h4 class="panel-title">최근 리워드 내역</h4></div>
  <div class="panel-body" style="padding:0;">
    <table class="table table-hover" style="margin:0;">
      <thead>
        <tr style="background:#f9f9f9;">
          <th>수령인</th>
          <th>구매자</th>
          <th>단계</th>
          <th style="text-align:right;">포인트</th>
          <th>상태</th>
          <th>일시</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentRewards)): ?>
        <?php foreach ($recentRewards as $r): ?>
        <tr>
          <td><?= gd_htmlspecialchars($r['receiver_mem_no']) ?></td>
          <td><?= gd_htmlspecialchars($r['buyer_mem_no']) ?></td>
          <td><span class="label label-info">L<?= $r['depth'] ?></span></td>
          <td style="text-align:right; color:#16a34a; font-weight:600;">+<?= number_format($r['final_points']) ?>P</td>
          <td>
            <span class="label <?= $r['status'] === '지급완료' ? 'label-success' : 'label-warning' ?>">
              <?= gd_htmlspecialchars($r['status']) ?>
            </span>
          </td>
          <td style="color:#aaa; font-size:12px;"><?= date('Y-m-d', strtotime($r['created_dt'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="6" style="text-align:center; color:#aaa; padding:24px;">데이터 없음</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

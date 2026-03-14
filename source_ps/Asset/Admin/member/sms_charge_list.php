<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 *
 * @var \Origin\DTO\Member\Sms\SmsPointChargeDTO[] $smsPointChargeList
 * @var \Bundle\Component\Page\Page $page
 */
?>
<div class="page-header js-affix">
    <h3><?= end($naviMenu->location); ?></h3>
</div>

<table class="table table-rows table-fixed">
    <colgroup>
        <col class="width10p"/>
        <col class="width20p"/>
        <col class="width20p"/>
        <col class="width20p"/>
        <col class="width20p"/>
    </colgroup>
    <thead>
    <tr>
        <th>번호</th>
        <th>내용</th>
        <th>결제방법</th>
        <th>결제가격</th>
        <th>결제일</th>
    </tr>
    </thead>
    <tbody class="">
        <?php if (empty($smsPointChargeList)) : ?>
            <tr>
                <td class="no-data" colspan="5">
                    SMS 포인트 충전 내역이 없습니다.
                </td>
            </tr>
        <?php endif; ?>
        <?php foreach ($smsPointChargeList as $smsPointCharge) : ?>
            <tr>
                <td class="center"><?= $page->idx--; ?></td>
                <td class="center"><?= $smsPointCharge->getDescription(); ?></td>
                <td class="center"><?= $smsPointCharge->getPayTypeName(); ?></td>
                <td class="center"><?= $smsPointCharge->getAmount(); ?></td>
                <td class="center"><?= $smsPointCharge->getPaidAt(); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="center"><?= $page->getPage(); ?></div>

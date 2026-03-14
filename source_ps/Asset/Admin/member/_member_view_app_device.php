<div class="table-title">
    모바일앱 정보
</div>
<div class="form-inline">
    <table class="table table-cols">
        <colgroup>
            <col class="width-sm"/>
            <col/>
        </colgroup>
        <tr>
            <th>혜택 정보</th>
            <td>
                <table class="table table-cols">
                    <colgroup>
                        <col class="width-md"/>
                        <col class="width-md"/>
                        <col class="width-md"/>
                        <col/>
                    </colgroup>
                    <tr>
                        <th>앱 설치 혜택</th>
                        <td><?=$installBenefitReceived;?></td>
                        <th>최초 로그인 일시</th>
                        <td>
                            <?=$firstLoginDateTime;?><br>
                            <p class="notice-danger notice-info">앱을 접속한 최초 일시입니다.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>앱 알림 혜택</th>
                        <td><?=$pushBenefitReceived;?></td>
                        <th>혜택 지급 일시</th>
                        <td>
                            <?=$pushBenefitReceivedDateTime?><br>
                            <p class="notice-danger notice-info">앱에서 알림 혜택이 지급된 일시입니다.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>앱 주문 혜택</th>
                        <td><?=$firstOrderBenefitReceived;?></td>
                        <th>최초 주문 일시</th>
                        <td>
                            <?=$firstOrderDateTime;?><br>
                            <p class="notice-danger notice-info">앱에서 결제완료한 최초 일시입니다.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <th>접속 정보</th>
            <td>
                <table class="table table-rows">
                    <colgroup>
                        <col class="width-xs"/>
                        <col class="width-md"/>
                        <col class="width-sm"/>
                        <col class="width-sm"/>
                        <col class="width-md"/>
                        <col/>
                    </colgroup>
                    <thead>
                    <tr>
                        <th>운영체제</th>
                        <th>모바일 기종</th>
                        <th>최근 로그인 일시</th>
                        <th>정보성 알림 수신 동의</th>
                        <th>광고성 알림 수신 동의</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="center"><?=$deviceInfos['platform'];?></td>
                        <td class="center"><?=$deviceInfos['deviceModel'];?></td>
                        <td class="center"><?=$deviceInfos['latestLoginDateTime'];?></td>
                        <td class="center"><?=$deviceInfos['isNotificationAgreed'];?></td>
                        <td class="center"><?=$deviceInfos['isAdNotificationAgreed'];?></td>
                    </tr>
                    </tbody>
                </table>
                <p class="notice-danger notice-info">최근 90일내 접속한 디바이스가 표시됩니다.</p>
            </td>
        </tr>
    </table>
</div>

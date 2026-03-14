#!/bin/bash
# source_ps 파일을 www(고도몰 웹루트)에 오버레이하는 스크립트

SOURCE_PS="../source_ps"
WWW="./www"

if [ ! -d "$WWW" ]; then
    echo "ERROR: $WWW 디렉토리가 없습니다. 먼저 고도몰5 소스를 www/ 에 넣어주세요."
    exit 1
fi

echo "source_ps 파일을 www/ 에 복사합니다..."

# Asset/Admin → www/adm (또는 www/admin, 실제 경로 확인 필요)
if [ -d "$SOURCE_PS/Asset/Admin" ]; then
    # 고도몰 관리자 경로에 맞게 수정하세요
    # 일반적으로 /adm 또는 /admin
    rsync -av --progress "$SOURCE_PS/Asset/Admin/" "$WWW/adm/"
    echo "Asset/Admin → www/adm/ 복사 완료"
fi

# Bundle → www/Bundle
if [ -d "$SOURCE_PS/Bundle" ]; then
    rsync -av --progress "$SOURCE_PS/Bundle/" "$WWW/Bundle/"
    echo "Bundle → www/Bundle/ 복사 완료"
fi

# skin → www/skin
if [ -d "$SOURCE_PS/skin" ]; then
    rsync -av --progress "$SOURCE_PS/skin/" "$WWW/skin/"
    echo "skin → www/skin/ 복사 완료"
fi

echo ""
echo "완료! http://localhost:8080 에서 확인하세요."

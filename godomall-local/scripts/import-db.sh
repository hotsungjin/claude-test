#!/bin/bash
# 운영 DB 덤프를 로컬 Docker MySQL에 임포트하는 스크립트
# 사용법: ./scripts/import-db.sh dump.sql

DUMP_FILE=$1
DB_NAME="godomall"
DB_USER="godomall"
DB_PASS="godomall123"

if [ -z "$DUMP_FILE" ]; then
    echo "사용법: $0 <dump파일.sql>"
    echo "예시: $0 ./backup.sql"
    exit 1
fi

if [ ! -f "$DUMP_FILE" ]; then
    echo "ERROR: $DUMP_FILE 파일을 찾을 수 없습니다."
    exit 1
fi

echo "DB 임포트 중: $DUMP_FILE → $DB_NAME"
docker exec -i godomall_mysql mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DUMP_FILE"

if [ $? -eq 0 ]; then
    echo "DB 임포트 완료!"
else
    echo "ERROR: DB 임포트 실패. 컨테이너가 실행 중인지 확인하세요."
fi

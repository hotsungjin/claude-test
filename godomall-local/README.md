# 고도몰5 로컬 Docker 개발환경

## 구성
- **Nginx** 1.24 → http://localhost:8080
- **PHP** 7.4-fpm
- **MySQL** 5.7 → localhost:3307
- **Redis** 6
- **phpMyAdmin** → http://localhost:8081

## 시작 순서

### 1단계: 고도몰5 전체 소스 준비
운영 서버에서 소스를 다운받아 `www/` 폴더에 넣습니다.

```bash
# 운영 서버에서 sftp/rsync로 다운로드 예시
rsync -avz user@운영서버IP:/var/www/html/ ./www/
# 또는
scp -r user@운영서버IP:/var/www/html/* ./www/
```

### 2단계: Docker 빌드 및 시작
```bash
cd godomall-local
docker compose up -d --build
```

### 3단계: DB 덤프 가져오기
운영 서버에서 DB를 덤프한 뒤 임포트합니다.

```bash
# 운영 서버에서 덤프
mysqldump -u [유저] -p[비번] [DB명] > backup.sql

# 로컬에 임포트
./scripts/import-db.sh backup.sql
```

### 4단계: source_ps 파일 반영
```bash
./scripts/deploy-source-ps.sh
```

### 5단계: 고도몰5 DB 접속 설정 수정
`www/` 안의 DB 설정 파일에서 로컬 DB 정보로 변경합니다.
- Host: `mysql` (Docker 내부 hostname)
- Port: `3306`
- DB명/유저/비번: `.env` 파일 참조

## 유용한 명령어
```bash
# 시작
docker compose up -d

# 중지
docker compose down

# PHP 컨테이너 접속
docker exec -it godomall_php bash

# MySQL 접속
docker exec -it godomall_mysql mysql -u godomall -pgodomall123 godomall

# 로그 확인
docker compose logs -f php
docker compose logs -f nginx
```

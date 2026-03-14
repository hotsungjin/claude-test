"""
공유 Jinja2Templates 인스턴스 — tojson 필터 포함
모든 라우터에서 이 모듈을 임포트해서 사용
"""
import json
from fastapi.templating import Jinja2Templates

templates = Jinja2Templates(directory="app/templates")


def _tojson(obj) -> str:
    return json.dumps(obj, ensure_ascii=False)


templates.env.filters["tojson"] = _tojson

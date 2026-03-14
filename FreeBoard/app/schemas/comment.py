from typing import Optional
from pydantic import BaseModel, field_validator


class CommentCreateSchema(BaseModel):
    content: str
    parent_id: Optional[int] = None

    @field_validator("content")
    @classmethod
    def content_not_empty(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("댓글 내용을 입력해주세요.")
        if len(v) > 2000:
            raise ValueError("댓글은 2000자 이내여야 합니다.")
        return v

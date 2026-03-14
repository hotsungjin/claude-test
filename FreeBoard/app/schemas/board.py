from pydantic import BaseModel, field_validator
from typing import Literal


class BoardCreateSchema(BaseModel):
    name: str
    description: str = ""
    read_perm: Literal["all", "member", "admin"] = "all"
    write_perm: Literal["member", "admin"] = "member"
    comment_perm: Literal["all", "member", "admin"] = "member"
    display_order: int = 0

    @field_validator("name")
    @classmethod
    def name_not_empty(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("게시판 이름을 입력해주세요.")
        if len(v) > 50:
            raise ValueError("게시판 이름은 50자 이내여야 합니다.")
        return v


class BoardUpdateSchema(BaseModel):
    name: str
    description: str = ""
    read_perm: Literal["all", "member", "admin"] = "all"
    write_perm: Literal["member", "admin"] = "member"
    comment_perm: Literal["all", "member", "admin"] = "member"
    display_order: int = 0
    is_active: bool = True

    @field_validator("name")
    @classmethod
    def name_not_empty(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("게시판 이름을 입력해주세요.")
        return v

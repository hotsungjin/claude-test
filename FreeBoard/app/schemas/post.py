from pydantic import BaseModel, field_validator


class PostCreateSchema(BaseModel):
    title: str
    content: str

    @field_validator("title")
    @classmethod
    def title_not_empty(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("제목을 입력해주세요.")
        if len(v) > 200:
            raise ValueError("제목은 200자 이내여야 합니다.")
        return v

    @field_validator("content")
    @classmethod
    def content_not_empty(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("내용을 입력해주세요.")
        return v


class PostUpdateSchema(PostCreateSchema):
    pass

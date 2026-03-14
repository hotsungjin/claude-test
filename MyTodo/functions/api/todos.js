export async function onRequestGet({ env }) {
  const { results } = await env.DB.prepare(
    "SELECT * FROM todos ORDER BY done ASC, created_at DESC"
  ).all();
  return Response.json(results);
}

export async function onRequestPost({ request, env }) {
  const { content, category = '기본' } = await request.json();
  if (!content || !content.trim()) {
    return Response.json({ error: "내용을 입력해주세요." }, { status: 400 });
  }
  const { success, meta } = await env.DB.prepare(
    "INSERT INTO todos (content, category) VALUES (?, ?)"
  ).bind(content.trim(), category).run();

  if (!success) {
    return Response.json({ error: "저장 실패" }, { status: 500 });
  }
  const todo = await env.DB.prepare(
    "SELECT * FROM todos WHERE id = ?"
  ).bind(meta.last_row_id).first();

  return Response.json(todo, { status: 201 });
}

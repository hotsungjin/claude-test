export async function onRequestPut({ request, env, params }) {
  const id = params.id;
  const body = await request.json();

  if ('done' in body) {
    await env.DB.prepare("UPDATE todos SET done = ? WHERE id = ?")
      .bind(body.done ? 1 : 0, id).run();
  }
  if ('content' in body && body.content?.trim()) {
    await env.DB.prepare("UPDATE todos SET content = ? WHERE id = ?")
      .bind(body.content.trim(), id).run();
  }
  if ('category' in body) {
    await env.DB.prepare("UPDATE todos SET category = ? WHERE id = ?")
      .bind(body.category, id).run();
  }

  const todo = await env.DB.prepare(
    "SELECT * FROM todos WHERE id = ?"
  ).bind(id).first();

  return Response.json(todo);
}

export async function onRequestDelete({ env, params }) {
  await env.DB.prepare("DELETE FROM todos WHERE id = ?")
    .bind(params.id).run();
  return Response.json({ message: "삭제되었습니다." });
}

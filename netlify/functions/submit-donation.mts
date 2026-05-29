import type { Config } from "@netlify/functions";
import { db } from "../../db/index.js";
import { donations } from "../../db/schema.js";

export default async (req: Request) => {
  if (req.method !== "POST") {
    return new Response("Method not allowed", { status: 405 });
  }

  let data: Record<string, string>;

  const contentType = req.headers.get("content-type") || "";
  if (contentType.includes("application/json")) {
    data = await req.json();
  } else {
    const formData = await req.formData();
    data = Object.fromEntries(formData.entries()) as Record<string, string>;
  }

  const { name, email, phone, amount, cause, payment_method, message, anonymous } = data;

  if (!name?.trim()) {
    return Response.json({ error: "Name is required." }, { status: 400 });
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
    return Response.json({ error: "Valid email is required." }, { status: 400 });
  }
  const amountNum = parseFloat(amount);
  if (isNaN(amountNum) || amountNum < 1) {
    return Response.json({ error: "Donation amount must be at least ₹1." }, { status: 400 });
  }

  try {
    await db.insert(donations).values({
      name: name.trim(),
      email: email.trim().toLowerCase(),
      phone: (phone || "").trim(),
      amount: amountNum,
      cause: (cause || "general").trim(),
      paymentMethod: (payment_method || "upi").trim(),
      message: (message || "").trim(),
      anonymous: anonymous === "on" || anonymous === "true" || anonymous === "1",
    });

    return Response.json({ success: true });
  } catch (err) {
    console.error("Donation insert error:", err);
    return Response.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
};

export const config: Config = {
  path: "/api/donate",
  method: "POST",
};

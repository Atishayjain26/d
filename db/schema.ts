import { pgTable, serial, text, timestamp, doublePrecision, boolean } from "drizzle-orm/pg-core";

export const donations = pgTable("donations", {
  id: serial().primaryKey(),
  name: text().notNull(),
  email: text().notNull(),
  phone: text().notNull().default(""),
  amount: doublePrecision().notNull(),
  cause: text().notNull().default("general"),
  paymentMethod: text("payment_method").notNull().default("upi"),
  message: text().notNull().default(""),
  anonymous: boolean().notNull().default(false),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

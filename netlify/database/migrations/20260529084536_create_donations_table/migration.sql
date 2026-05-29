CREATE TABLE "donations" (
	"id" serial PRIMARY KEY,
	"name" text NOT NULL,
	"email" text NOT NULL,
	"phone" text DEFAULT '' NOT NULL,
	"amount" double precision NOT NULL,
	"cause" text DEFAULT 'general' NOT NULL,
	"payment_method" text DEFAULT 'upi' NOT NULL,
	"message" text DEFAULT '' NOT NULL,
	"anonymous" boolean DEFAULT false NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL
);

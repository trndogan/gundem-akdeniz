import { defineConfig } from "drizzle-kit";
import path from "path";

export default defineConfig({
  dialect: "postgresql",
  schema: [
    "./src/collections/*.ts",
    "./src/payload.config.ts",
  ],
  out: "./drizzle",
  dbCredentials: {
    url: process.env.DATABASE_URI || "postgres://postgres:postgres@localhost:5432/akdenizgundem",
  },
});

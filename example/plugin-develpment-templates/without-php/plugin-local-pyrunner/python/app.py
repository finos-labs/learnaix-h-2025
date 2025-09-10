from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class AnalyzeRequest(BaseModel):
    text: str

@app.post("/api/analyze")
def analyze(req: AnalyzeRequest):
    # Example logic: simple echo with sentiment
    return {"sentiment": "positive", "score": 0.95, "received": req.text}

# openai_service.py

from openai import OpenAI
import os
import time
from typing import Optional, List, Dict, Any, Tuple

import config

client = OpenAI(api_key=config.OPENAI_API_KEY)

# --- Assistant Operations ---
# (Existing functions remain unchanged)

def create_openai_assistant(name: str, instructions: str, model: str, temperature: float, vector_store_id: Optional[str] = None) -> Dict[str, Any]:
    """Creates an assistant in OpenAI with the file_search tool."""
    tools = [{"type": "file_search"}]
    tool_resources = {}
    if vector_store_id:
        tool_resources = {"file_search": {"vector_store_ids": [vector_store_id]}}

    assistant = client.beta.assistants.create(
        name=name,
        instructions=instructions,
        model=model,
        temperature=temperature,
        tools=tools,
        tool_resources=tool_resources
    )
    return assistant.dict()

def get_openai_assistant(openai_assistant_id: str) -> Dict[str, Any]:
    """Retrieves an assistant from OpenAI."""
    assistant = client.beta.assistants.retrieve(openai_assistant_id)
    return assistant.dict()

def update_openai_assistant(openai_assistant_id: str, name: Optional[str] = None, instructions: Optional[str] = None, temperature: Optional[float] = None, add_vector_store_ids: Optional[List[str]] = None, remove_vector_store_ids: Optional[List[str]] = None) -> Dict[str, Any]:
    """Updates an assistant in OpenAI."""
    current_assistant = client.beta.assistants.retrieve(openai_assistant_id)

    update_params = {}
    if name is not None:
        update_params["name"] = name
    if instructions is not None:
        update_params["instructions"] = instructions
    if temperature is not None:
        update_params["temperature"] = temperature

    current_vector_store_ids = []
    if current_assistant.tool_resources and current_assistant.tool_resources.file_search:
        current_vector_store_ids = current_assistant.tool_resources.file_search.vector_store_ids or []

    if add_vector_store_ids or remove_vector_store_ids:
        new_vector_store_ids_set = set(current_vector_store_ids)
        if add_vector_store_ids:
            new_vector_store_ids_set.update(add_vector_store_ids)
        if remove_vector_store_ids:
            new_vector_store_ids_set.difference_update(remove_vector_store_ids)

        if {"type": "file_search"} not in current_assistant.tools:
            current_assistant.tools.append({"type": "file_search"})
        update_params["tools"] = current_assistant.tools

        update_params["tool_resources"] = {
            "file_search": {"vector_store_ids": list(new_vector_store_ids_set)}
        }

    assistant = client.beta.assistants.update(
        openai_assistant_id=openai_assistant_id,
        **update_params
    )
    return assistant.dict()


def delete_openai_assistant(openai_assistant_id: str):
    """Deletes an assistant from OpenAI."""
    client.beta.assistants.delete(openai_assistant_id)

# --- File Operations ---
# (Existing functions remain unchanged)

def upload_openai_file(file_path: str) -> Dict[str, Any]:
    """Uploads a file to OpenAI and returns its ID."""
    with open(file_path, "rb") as f:
        file_obj = client.files.create(file=f, purpose="assistants")
    return file_obj.dict()

def delete_openai_file(openai_file_id: str):
    """Deletes a file from OpenAI."""
    client.files.delete(openai_file_id)

# --- Vector Store Operations ---
# (Existing functions remain unchanged)

def create_openai_vector_store(name: str) -> Dict[str, Any]:
    """Creates a Vector Store in OpenAI."""
    vector_store = client.vector_stores.create(name=name)
    return vector_store.dict()

def get_openai_vector_store(openai_vector_store_id: str) -> Dict[str, Any]:
    """Retrieves a Vector Store from OpenAI."""
    vector_store = client.beta.vector_stores.retrieve(openai_vector_store_id)
    return vector_store.dict()

def delete_openai_vector_store(openai_vector_store_id: str):
    """Deletes a Vector Store from OpenAI."""
    client.beta.vector_stores.delete(openai_vector_store_id)

def add_file_to_vector_store(openai_vector_store_id: str, openai_file_id: str) -> Dict[str, Any]:
    """Adds a file to a Vector Store in OpenAI."""
    vector_store_file = client.beta.vector_stores.files.create(
        vector_store_id=openai_vector_store_id,
        file_id=openai_file_id
    )
    return vector_store_file.dict()

def get_vector_store_file_status(openai_vector_store_id: str, openai_vector_store_file_id: str) -> str:
    """Gets the processing status of a file within a Vector Store."""
    file_status = client.beta.vector_stores.files.retrieve(
        vector_store_id=openai_vector_store_id,
        file_id=openai_vector_store_file_id
    )
    return file_status.status

def remove_file_from_vector_store(openai_vector_store_id: str, openai_file_id: str):
    """
    Removes a file from a specific Vector Store in OpenAI.
    This operation does NOT delete the file from OpenAI's global file storage.
    It only removes its association with the given Vector Store.
    """
    try:
        client.beta.vector_stores.files.delete(
            vector_store_id=openai_vector_store_id,
            file_id=openai_file_id
        )
        print(f"File {openai_file_id} successfully removed from Vector Store {openai_vector_store_id} in OpenAI.")
    except Exception as e:
        print(f"Error removing file {openai_file_id} from Vector Store {openai_vector_store_id} in OpenAI: {e}")
        raise e

# --- Thread (Chat) Operations ---

def create_openai_thread() -> Dict[str, Any]:
    """Creates a new conversation thread in OpenAI."""
    thread = client.beta.threads.create()
    return thread.dict()

def add_message_to_thread(openai_thread_id: str, content: str, role: str = "user") -> Dict[str, Any]:
    """Adds a message to an existing OpenAI thread."""
    message = client.beta.threads.messages.create(
        thread_id=openai_thread_id,
        role=role,
        content=content,
    )
    return message.dict()

def run_assistant_on_thread(openai_thread_id: str, openai_assistant_id: str) -> Dict[str, Any]:
    """Initiates a run of an assistant on a thread."""
    run = client.beta.threads.runs.create(
        thread_id=openai_thread_id,
        assistant_id=openai_assistant_id,
    )
    return run.dict()

def wait_for_run_completion(openai_thread_id: str, openai_run_id: str) -> Dict[str, Any]:
    """Polls the run status until it completes."""
    while True:
        run = client.beta.threads.runs.retrieve(thread_id=openai_thread_id, run_id=openai_run_id)
        if run.status in ['completed', 'failed', 'cancelled', 'expired']:
            return run.dict()
        time.sleep(0.5) # Poll every 0.5 seconds

def get_latest_assistant_message(openai_thread_id: str) -> Optional[str]:
    """Retrieves the latest assistant message from a thread."""
    messages_page = client.beta.threads.messages.list(thread_id=openai_thread_id, order="desc", limit=1)
    if messages_page.data:
        # Filter to ensure it's an assistant message
        for message in messages_page.data:
            if message.role == "assistant":
                # Assuming the assistant's response is in the first content block and is text
                for content_block in message.content:
                    if content_block.type == "text":
                        return content_block.text.value
    return None

def delete_openai_thread(openai_thread_id: str):
    """Deletes an OpenAI thread."""
    client.beta.threads.delete(openai_thread_id)
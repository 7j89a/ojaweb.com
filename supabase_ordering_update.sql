-- الخطوة 1: إضافة حقل "question_order" لجدول الأسئeلة
-- This column will store the display order of the questions.
ALTER TABLE public.questions
ADD COLUMN question_order INTEGER;

-- الخطوة 2: إنشاء دالة لتعيين الترتيب المبدئي للأسئلة الحالية
-- This function assigns an order based on the creation time for each model's questions.
-- You only need to run this ONCE after creating it.
CREATE OR REPLACE FUNCTION set_initial_question_order()
RETURNS void AS $$
BEGIN
  UPDATE public.questions q
  SET question_order = sub.rn
  FROM (
    SELECT 
      id, 
      ROW_NUMBER() OVER (PARTITION BY model_id ORDER BY created_at, id) as rn
    FROM public.questions
  ) AS sub
  WHERE q.id = sub.id AND q.question_order IS NULL;
END;
$$ LANGUAGE plpgsql;

-- ملاحظة: بعد إنشاء الدالة أعلاه، قم بتنفيذ الأمر التالي مرة واحدة فقط من محرر SQL في Supabase
-- SELECT set_initial_question_order();


-- الخطوة 3: إنشاء دالة لتحديث ترتيب الأسئلة
-- This is much more efficient than sending one update request per question from the frontend.
-- It takes an array of question IDs in the desired order and updates their 'question_order' field.
CREATE OR REPLACE FUNCTION update_question_order(p_question_ids BIGINT[])
RETURNS void AS $$
DECLARE
    v_model_id BIGINT;
BEGIN
    -- Ensure all questions belong to the same model to prevent mix-ups.
    -- This gets the model_id from the first question in the list.
    SELECT model_id INTO v_model_id FROM public.questions WHERE id = p_question_ids[1];

    -- If no model_id is found (e.g., invalid question ID), raise an error.
    IF v_model_id IS NULL THEN
        RAISE EXCEPTION 'Invalid question ID provided.';
    END IF;

    -- Perform the update
    UPDATE public.questions
    SET question_order = array_position(p_question_ids, id)
    WHERE id = ANY(p_question_ids) AND model_id = v_model_id;

END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- الخطوة 4: إنشاء دالة لنسخ سؤال
-- This function will create a complete copy of a question, including its options,
-- and place it at the end of the quiz.
CREATE OR REPLACE FUNCTION duplicate_question(p_question_id BIGINT)
RETURNS TABLE(new_question_id BIGINT) AS $$
DECLARE
    original_question RECORD;
    new_id BIGINT;
    max_order INTEGER;
BEGIN
    -- 1. Find the original question
    SELECT * INTO original_question FROM public.questions WHERE id = p_question_id;

    -- If not found, exit
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Question with ID % not found', p_question_id;
    END IF;

    -- 2. Find the highest order number for that model_id
    SELECT COALESCE(MAX(question_order), 0) INTO max_order
    FROM public.questions
    WHERE model_id = original_question.model_id;

    -- 3. Insert the new question
    INSERT INTO public.questions (
        model_id,
        question_text,
        question_image,
        options,
        correct,
        explanation,
        time_limit_seconds,
        needs_calculator,
        question_order -- Set the new order
    )
    VALUES (
        original_question.model_id,
        original_question.question_text || ' (نسخة)', -- Append "(Copy)"
        original_question.question_image,
        original_question.options,
        original_question.correct,
        original_question.explanation,
        original_question.time_limit_seconds,
        original_question.needs_calculator,
        max_order + 1 -- Place it at the end
    )
    RETURNING id INTO new_id;

    -- 4. Return the new ID
    RETURN QUERY SELECT new_id;

END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

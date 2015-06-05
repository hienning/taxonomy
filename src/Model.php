<?php namespace Hienning\Taxonomy;


/**
 * The data structure is a typical tree that consists of several nodes
 * hierarchically.
 *
 * I borrowed some concepts from Drupal's taxonomy module, to name some
 * nodes:
 *
 * 1) The top-most node is the "root" node. By preserve this stub, we can
 *    count the sub-nodes conveniently.
 * 2) The direct decendant of the root is the "vocabulary" node;
 * 3) All child nodes of vocabulary, are the terms;
 *
 * See the following for intuitive explanations.
 *
 * Root                         <-- the root
 *   |
 *   +-- Electronics            <-- vocabulary
 *         |
 *         +-- Mp3 Players      <-- term
 *         |
 *         +-- CD Playeyrs      <-- term
 *
 * @package Hienning\Taxonomy
 */
class Model extends \Illuminate\Database\Eloquent\Model
{


    const ROOT_ID   = 1;


    const ROOT_CODE = 'root';


    const ROOT_NAME = 'Root';



    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'taxonomy';




    /**
     * Return root node.
     *
     * @return Model|\Illuminate\Support\Collection|null|static
     */
    public static function root()
    {
        $root = self::find(1);

        if (!$root) {
            $root          = new self;
            $root->name    = self::ROOT_NAME;
            $root->code    = self::ROOT_CODE;
            $root->depth   = 0;
            $root->left    = 1;
            $root->right   = 2;

            $root->save();
        }

        return $root;
    }



    /**
     * Retrieve a term/vocabulary by its code name.
     *
     * @param $code
     *
     * @return TaxonomyModel
     */
    public static function findByCode($code)
    {
        return self::where('code', '=', $code)->first();
    }



    /**
     * Get the number of direct descendant(s).
     *
     * @return integer
     */
    public function childrenCount()
    {
        return ($this->right - $this->left - 1) / 2;
    }



    /**
     * Fetch the all the direct decendants.
     *
     * @return \Illuminate\Support\Collection
     */
    public function children()
    {
        return self::where('left', '>', $this->left)
                   ->where('right', '<', $this->right)
                   ->where('depth', '=', $this->depth+1)
                   ->orderBy('left')
                   ->get();
    }



    /**
     * Fetch the last (i.e. the right most) child term.
     *
     * @return mixed
     */
    public function lastTerm()
    {
        $refreshed = self::find($this->id);

        $depth = $refreshed->depth + 1;
        $right = $refreshed->right - 1;

        return self::where('depth', '=', $depth)->where('right', '=', $right)->first();
    }



    /**
     * Append a child item to current term/vocabulary.
     *
     * @param $name String    The name for the term, which can be used for tittle, caption, etc.
     * @param $code String    A code name use to identify the the term/vocabulary, which usually stands for
     *                        URL, internal ID, etc.
     * @return \Hienning\Taxonomy\Model     The new taxonomy term that created.
     * @throws Exception
     */
    public function addTerm($name, $code)
    {
        try {
            \DB::beginTransaction();

            $lastTerm = $this->lastTerm();

            $rightVal = ($lastTerm ? $lastTerm->right : $this->left);

            $this->where('right', '>', $rightVal)->increment('right', 2);
            $this->where('left', '>', $rightVal)->increment('left', 2);

            $term = new self;
            $term->depth   = $this->depth + 1;
            $term->name    = trim($name);
            // ToDo: Prevent from creating a term with an existed code
            $term->code    = trim($code);
            $term->left    = $rightVal + 1;
            $term->right   = $rightVal + 2;

            $term->save();

            \DB::commit();
            return $term;
        } catch(Exception $e) {
            \DB::rollBack();
            throw new Exception($e);
        }
    }



    public function deleteTerm()
    {
        // ToDo: Should remove term without sub-terms only
    }



    public function deleteTermRecursive()
    {
        try {
            \DB::beginTransaction();

            $width = $this->right - $this->left + 1;

            $this->whereBetween('left', array($this->left, $this->right))->delete();
            $this->where('right', '>', $this->right)->decrement('right', $width);
            $this->where('left', '>', $this->right)->decrement('left', $width);
            $this->delete();

            \DB::commit();

        } catch(Exception $e) {

            \DB::rollBack();
            throw new Exception($e);

        }
    }



    /**
     * Remove but don't delete specified sub-taxonomy from the taxonomy, and
     * then insert that sub-taxonomy after target taxonomy, as it's sub-item.
     *
     * @param mixed	parent
     * @param mixed	after
     *
     * @throws Exception
     */
    public function moveTo($parent, $after = null)
    {
        try {
            DB::beginTransaction();

            $this->doMoveTo($parent, $after);

            DB::commit();

        } catch(Exception $e) {

            DB::rollBack();
            throw new Exception($e);
        }
    }



    /**
     * @param $parent
     * @param $after
     *
     * @throws Exception
     */
    protected function doMoveTo($parent, $after)
    {
        $parentValid = is_int($parent) || is_string($parent);
        $afterValid = $after && (is_int($after) || is_string($after));

        if ($parentValid && $afterValid) {

            // Place after specified $term
            $target = (is_string($after) ? self::findByCode($after) : self::find($after));

            $rightVal = $target->right;
            $parentTerm = (is_string($parent) ? self::findByCode($parent) : self::find($parent));
            $targetParent = $parentTerm->id;

            $extraDepth = ($this->depth !== $target->depth ? 1 : 0);

        } else if ($parentValid && !$after) {

            // Append
            $target = (is_string($parent) ? self::findByCode($parent) : self::find($parent));
            $extraDepth = 1;
            $targetParent = $target->id;

            if ($target->childrenCount()) {
                $target = $target->lastTerm();
                $rightVal = $target->right;
            } else {
                $rightVal = $target->left;
            }

        }

        if (!$target) {
            throw new Exception('[DB] Non-exist targetId: ' . $parent);
        }

        $gap = $this->right - $this->left + 1;
        $taxToMove = $this->whereBetween('left', [$this->left, $this->right])->lists('id');

        if ($this->right < $target->left) {

            // Move from left side of target,
            // need to subtract all taxonomies that between $target and $this by $gap

            $this->where('left', '>', $this->right)
                 ->where('right', '<=', $rightVal)
                 ->whereNotIn('id', [$targetParent])
                 ->decrement('right', $gap);

            $this->where('left', '>', $this->right)
                 ->where('left', '<=', $target->left)
                 ->decrement('left', $gap);

            $distance = $this->right - $rightVal;

        } else {

            // Move from right side of target,
            // need to add all taxonomies that between $target and $this by $gap

            $this->where('right', '>', $rightVal)
                 ->where('right', '<', $this->left)
                 ->increment('right', $gap);

            $this->where('left', '>', $rightVal)
                 ->where('left', '<', $this->left)
                 //->whereNotIn('id', [$targetParent])
                 ->increment('left', $gap);

            $distance = $this->left - $rightVal - 1;

        }

        // fix all moved terms
        self::whereIn('id', $taxToMove)->update([
            'left'  => DB::raw('`left` - (' . $distance . ')'),
            'right' => DB::raw('`right` - (' . $distance . ')'),
            'depth' => DB::raw('`depth` - ' . $this->depth . ' + ' . $extraDepth . ' + ' . $target->depth),
        ]);
    }
}

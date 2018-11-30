/* stack operation
->push
->pop
->display
*/
# include<iostream>

using namespace std;

class Stack
{
    int top;
    int n;
    public:
    int *a;
    Stack(int k)
    {
        top = -1;
        n=k;
        a=new int[n];
    }


    void push(int x);
    int pop();
    void isEmpty();
    void display();
};


void Stack::push(int x)
{
    if(top >= n)
    {
        cout << "Stack Overflow \n";
    }
    else
    {
        a[++top] = x;
        cout << "Element Inserted \n";
    }
}


int Stack::pop()
{
    if(top < 0)
    {
        cout << "Stack Underflow \n";
        return 0;
    }
    else
    {
        int d = a[top--];
        return d;
    }
}
void Stack ::display()
{
if(top==-1)
{
cout<<"stack is empty"<<endl;
return;
}
  for(int i=top;i>=0;i--)
  {
  cout<<a[i]<<" ";

  }
  cout<<endl;
}


void Stack::isEmpty()
{
    if(top < 0)
    {
        cout << "Stack is empty \n";
    }
    else
    {
        cout << "Stack is not empty \n";
    }
}

// main function
int main() {

    int n,e,k;
    cout<<"enter size of stack"<<endl;
    cin>>k;

    Stack s1(k);

    cout<<"1 for push"<<endl;
      cout<<"2 for pop"<<endl;
        cout<<"3 for display"<<endl;
         cout<<"4 for end"<<endl;
    while(1)
    {
    cout<<"enter choice"<<endl;
      cin>>n;
      switch(n){
      case 1:
      cout<<"enter element to be push in stack :"<<endl;
      cin>>e;
      s1.push(e);
      break;
      case 2:
      s1.pop();
       break;
      case 3:
      s1.display();
       break;
    case 4:
    return 0;
    }


    }

}

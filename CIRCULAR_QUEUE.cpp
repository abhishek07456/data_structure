/* Q: Menu driven Program to demonstrate the implementation of various operations on a Circular queue .*/
#include<iostream>
using namespace std;
#include<iostream>

using namespace std;



class CircularQueue
{
    int *a;
    int rear;   //same as tail
    int front;  //same as head
int SIZE;
    public:
    CircularQueue(int k)
    {
     SIZE =k;
    a=new int[k];
        rear =-1;
         front = -1;
    }

    // function to check if queue is full
    bool isFull()
    {
        if(front == 0 && rear == SIZE - 1)
        {
            return true;
        }
        if(front == rear + 1)
        {
            return true;
        }
        return false;
    }

    // function to check if queue is empty
    bool isEmpty()
    {
        if(front == -1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    void enqueue(int x);
    int dequeue();
    void display();
    int size();
};


void CircularQueue :: enqueue(int x)
{
    if(isFull())
    {
        cout << "Queue is full";
    }
    else
    {
        if(front == -1)
        {
            front = 0;
        }
        rear = (rear + 1) % SIZE;
        a[rear] = x;
        cout << endl << "Inserted " << x << endl;
    }
}

int CircularQueue :: dequeue()
{
    int y;

    if(isEmpty())
    {
        cout << "Queue is empty" << endl;
    }
    else
    {
        y = a[front];
        if(front == rear)
        {

            front = -1;
            rear = -1;
        }
        else
        {
            front = (front+1) % SIZE;
        }
        return(y);
    }
}

void CircularQueue :: display()
{

    int i;
    if(isEmpty())
    {
        cout << endl << "Empty Queue" << endl;
    }
    else
    {
        cout << endl << "Front -> " << front;
        cout << endl << "Elements -> ";
        for(i = front; i != rear; i= (i+1) % SIZE)
        {
            cout << a[i] << "\t";
        }
        cout << a[i];
        cout << endl << "Rear -> " << rear;
    }
}

int CircularQueue :: size()
{
    if(rear >= front)
    {
        return (rear - front) + 1;
    }
    else
    {
        return (SIZE - (front - rear) + 1);
    }
}
int main()
{
	int c,k;

	cout<<"enter size of queue: "<<endl;
	cin>>k;
	CircularQueue q(k);
		cout<<" 1.Push \n 2.Pop \n 3.Pip \n 4.Exit"<<endl;
	while(1)
	{
		cout<<endl<<"enter the choice : ";
		cin>>c;
		switch(c)
		{
			case 1:
				{
				  cout<<"enter element:"<<endl;
				  cin>>k;
				    q.enqueue(k);
					break;
				}
			case 2:
				{
					q.dequeue();
					break;
				}
			case 3:
				{
					q.display();
					break;
				}
			case 4:
				{
				   return 0;
				}
		}
	}
	return 0;
}
